<?php /*ąś*/
/*
 * CopyRight by Łukasz Kończak 
 */
class db{
	private $link=false;
	private $query;
	private $insert_id;
	private $aso;
	private $allowLisy;
	private $nameKey;
	public  $rowsCount;
	public  $result;
	public function setAllowLisy($array=null)
	{
		if(!is_null($array) && count($array)>0)
		{
			$this->allowLisy = $array;
		}
	}
	public function db($host,$user,$pass)
	{
		$this->link=@mysql_connect($host,$user,$pass,true);
		if(!$this->link)throw new Exception("błąd połączenia");
	}
	public function ask($query,$retNow=false,$aso=false,$nameKey=false,$debug = false)
	{
		if($debug)echo "ask($query,$retNow=false,$aso=false,$nameKey=false)",$this->link , '<br />';
		if(!$this->link)
		{
			throw new Exception("błąd połącznie");
		}
		$this->query = $query;
		$res = mysql_query($this->query, $this->link);
		$this->insert_id = mysql_insert_id($this->link);
		if(mysql_errno($this->link)>0)
		{
			throw new Exception($this->query.':'.mysql_errno($this->link).':'.  mysql_error($this->link));
		}
		if(!$res)
		{
			return false;
		}
		elseif($res===true)
		{
			return null;
		}
		$count = 0;
		$row= array();
		$rowTM=null;
		if($nameKey!==false && !isset($this->nameKey)) $this->nameKey = $nameKey;
		$retType = ($this->aso?MYSQL_ASSOC:MYSQL_NUM);
		if(!empty($this->allowLisy))
		{
			if($debug )echo __line__,'<br/>';
			if(isset($this->nameKey) && $this->nameKey<>'')
			{
				if($debug )echo __line__,'<br/>';
				while ($rowTM = mysql_fetch_array($res,$retType))
				{
					$row[$rowTM[$this->nameKey]] =array_intersect_key($rowTM, array_flip($this->allowLisy));
					$count++;
				}
			}
			else
			{
				if($debug )echo __line__,'<br/>';
				while ($rowTM = mysql_fetch_array($res,$retType))
				{
					$row[] = array_intersect_key($rowTM, array_flip($this->allowLisy));
					$count++;
				}
			}
			unset($this->allowLisy,$this->nameKey);
		}
		else
		{
			if($debug )echo __line__,'<br/>';
			if(isset($this->nameKey) && $this->nameKey<>'')
			{
				if($debug )echo __line__,':',$this->nameKey,'<br/>';
				while ($rowTM = mysql_fetch_array($res,$retType))
				{
					$row[$rowTM[$this->nameKey]] = $rowTM;
					$count++;
				}
				if($debug )print_r($row);
				unset($this->nameKey);
			}
			else
			{
				if($debug )echo __line__,'<br/>';
				while ($rowTM = mysql_fetch_array($res,$retType))
				{
					$row[] = $rowTM;
					$count++;
				}
			}
		}
		mysql_free_result($res);
		if(!$retNow)
		{
			if($debug )echo __line__,'<br/>';
			$this->rowsCount=$count;
			return $this->result = $row;
		}
		else
		{
			if($debug )echo __line__,'<br/>';
			return $row;
		}
	}
	public function getDatabases()
	{
		$this->aso = false;
		return $this->ask("SHOW DATABASES ");
	}
	public function getTables()
	{
		$this->nameKey = 'Name';
		$this->allowLisy = array('Name','Engine','Rows','Collation','Data_length');
		$this->aso = true;
		return $this->ask("SHOW TABLE STATUS");
	}
	public function getTableInfo($tab)
	{
		$this->aso = true;
		$this->nameKey='Field';
		return $this->ask("SHOW FULL COLUMNS FROM $tab");
	}
	public function getTableIndex($tab)
	{
		$this->aso = true;
		return $this->ask("SHOW INDEX FROM $tab");
	}
}
if(isset($_GET['src']))
{
	$str = file_get_contents(basename(__FILE__));
	$str = preg_replace('/('.chr(35).'demoCut)(.*)('.chr(35).'demoCutEnd)/is','',$str);
	if($_GET['src']!= 'get')
	{
		header("Content-Type: text/html; charset=UTF-8");
		echo '<code>',htmlspecialchars($str),'</code>' ;
	}
	else
	{
		header("Content-Type: text/palin-text");
		echo $str;
	}
	die();
}
$message = "";
session_start();
if(isset($_GET['resrt']))
{
	session_destroy();
	session_start();
	header("Location: ?");
	return;
}
if(isset($_GET['onlyDiff']))
{
	$_SESSION['onlyDiff']=(bool)$_GET['onlyDiff'];
}
if(!isset($_SESSION['onlyDiff']))
{
	$_SESSION['onlyDiff']=false;
}
$onlyDiff=$_SESSION['onlyDiff'];

if(isset($_POST['db1'])||isset($_POST['db2']))
{
	$id = (isset($_POST['db1'])?1:(isset($_POST['db2'])?2:0));
	$conn = @mysql_connect($_POST['host'].':'.$_POST['port'],$_POST['user'],$_POST['pass']);
	if($conn && $id>0)
	{
		if($id==1 || $id==2)
		{
			$_SESSION['db'][$id]['host']=$_POST['host'];
			$_SESSION['db'][$id]['port']=$_POST['port'];
			$_SESSION['db'][$id]['user']=$_POST['user'];
			$_SESSION['db'][$id]['pass']=$_POST['pass'];
		}
		mysql_close();
		header("Location: ?");
		return;
	}
}
if(isset($_GET['dbReset']))
{
	unset($_SESSION['db'][1]['db'],$_SESSION['db'][2]['db']);
}
$db1 = null;
$db2 = null;
if(isset($_SESSION['db'][1]))
{
	$db1 = new db($_SESSION['db'][1]['host'].':'.$_SESSION['db'][1]['port'],$_SESSION['db'][1]['user'],$_SESSION['db'][1]['pass']);
	if(isset($_SESSION['db'][1]['db']) && $_SESSION['db'][1]['db']<>'')
	{
		$db1->ask("use ".$_SESSION['db'][1]['db']);
	}
}
if(isset($_SESSION['db'][2]))
{
	$db2 = new db($_SESSION['db'][2]['host'].':'.$_SESSION['db'][2]['port'],$_SESSION['db'][2]['user'],$_SESSION['db'][2]['pass']);
	if(isset($_SESSION['db'][2]['db']) && $_SESSION['db'][2]['db']<>'')
	{
		$db2->ask("use ".$_SESSION['db'][2]['db']);
	}
}

if(isset($_POST['useDB1'])||isset($_POST['useDB2']))
{
	$dbTab=array();
	$id = (isset($_POST['useDB1'])?1:(isset($_POST['useDB2'])?2:0));
	try
	{
		$dbName = mysql_escape_string($_POST['useDB']);
		if($id==1)
		{
			foreach($db1->getDatabases() as $tm)
			{
				$dbTab[]=$tm[0];
			}
			if(in_array($dbName,$dbTab))
			{
				$db1->ask("use $dbName");
				$_SESSION['db'][$id]['db']=$dbName;
				header("Location: ?");
				return;
			}
			else
			{
				$message.="wybrana baza danych nie istnieje  <br />";
			}
		}
		elseif($id==2)
		{
			foreach($db2->getDatabases() as $tm)
			{
				$dbTab[]=$tm[0];
			}
			if(in_array($dbName,$dbTab))
			{
				$db2->ask("use $dbName");
				$_SESSION['db'][$id]['db']=$dbName;
				header("Location: ?");
				return;
			}
			else
			{
				$message.="wybrana baza danych nie istnieje <br />";
			}

		}
	}
	catch(Exception $ex)
	{
		die($ex);
	}
}
echo '<?xml version="1.0" encoding="UTF-8"?>';
?>
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml"><head><title>Porównywanie struktur idanych z baz MySQL</title><META NAME="Author" CONTENT="Łukasz Kończak, l.konczak@gmail.com"><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><style type="text/css"> body{background-color:#333333;color:#888888;font-family: Arial, Helvetica, Sans-Serif;margin:0;padding:0;} textarea {background-color:#252525;color:#888888;} #menuGlowne{ margin-left: 0; margin-right: 0; margin-top: 10px; margin-bottom: 10px; background-color: #171717; border-color: #333333; border-top-width: 1px; border-left-width: 0; border-right-width: 0; border-bottom-width: 0; border-style: solid; padding: 10px; font-size: 14px; } #mainTable{margin:5px auto;} #mainTable td {vertical-align:top;}  input {background-color:#333333;border:1px solid #171717;} input[type=submit] {border:1px solid #84d20c;color:#84d20c;float:right;} #menuGlowne a:visited, #menuGlowne a {color:#666666;text-decoration:none;} #menuGlowne a:hover{color:#ffffff;text-decoration:underline;} .dbHead{ border:1px solid #444444; background-color:#252525; padding: 10px; } .dbName { font-weight: bolder; color: white; padding: 10px; } #reset {float:right;} .dbName a, .toCmpCont a {color:#fff;} #menuGlowne a#reset, .toCmpCont a.warning, .warning {color:#d3430c;font-weight:bold;} .equ{color:#84d20c;} .notEqu{color:#d3430c;} .row_0{background-color:#444444;} .toCmpCont{background:#333333;} .row_1{} .cmpConHead{background-color:#171717; } .cmpConHead td {padding:5px;} #prpareCmpCont{margin:10px;} #prpareCmpCont .cmpConName {margin: 10px 0;} .cpSh, .cpBy,.cpOrder{margin:0 10px; } label{margin-right:10px;display:block;font-size:9pt;} label .fieldName{padding:4px;font-size:12pt;} .field{display:block;margin-left:10px;} a,a:visited {color:#fff;}a:hover{color:#84d20c} </style></head><body><div id="menuGlowne"><a href="?resrt" class="warning" id="reset"  title="przejdź do wpisywania połączenia do bazy">Wyczyść połaczenia</a>
		<?php
		if($onlyDiff)
		{
			echo '<a href="?onlyDiff=0">Pokazuj wszystkie</a>';
		}
		else
		{
			echo '<a href="?onlyDiff=1">Pokazuj tylko różne</a>';
		}
		if(isset($_SESSION['db'][1]['db']) || isset($_SESSION['db'][2]['db']))
		{
			echo ' <a href="?dbReset=1" title="przejdź do wyboru bazy danych">Wyczyść bazy</a>';
		}
		?>
		<a href="?" title="jeśli jeteś w porónywaniu danych możesz przejść do wyboru tabel">Lista</a>
		<a href="?src=get" title="jeśli Ci się spodobało możesz pobrać ten plik">pobierz plik</a>
		<a href="http://konczak.com.pl/kontakt.php" style="float:right;margin-right:10px;" title="pomysł i wykonanie Ł.Kończak">kontakt</a>
	</div>
	<div class="warning"><?php echo $message ?></div>
	<table id="mainTable" style="display:table;">
		<tr>
			<td></td>
			<td class="db1 head">
				<div class="dbHead">
					<div class="dbName">
						<?php echo (!isset($_SESSION['db'][1])?" Not connect":$_SESSION['db'][1]['user'].'@'.$_SESSION['db'][1]['host'].':'.$_SESSION['db'][1]['port'].(isset($_SESSION['db'][1]['db'])&&$_SESSION['db'][1]['db']!=''?' - '.$_SESSION['db'][1]['db']:'' ));	?>
					</div>
				</div>
			</td>
			<td class="db2 head">
				<div class="dbHead">
					<div class="dbName">
					<?php echo (!isset($_SESSION['db'][2])?" Not connect":$_SESSION['db'][2]['user'].'@'.$_SESSION['db'][2]['host'].':'.$_SESSION['db'][2]['port'].(isset($_SESSION['db'][2]['db'])&& $_SESSION['db'][2]['db']!=''?' - '.$_SESSION['db'][2]['db']:'' )); ?>
					</div>
				</div>
			</td>
		</tr>
		<tr>
			<td></td>
			<?php if(!isset($_SESSION['db'][1])): ?>
				<td class="db1 conn">
					<?php echo getConnectForm(1); ?>
				</td>
			<?php elseif(!isset($_SESSION['db'][1]['db'])|| $_SESSION['db'][1]['db']==''): ?>
				<td class="db1">
					Użyj bazy danych:<?php echo getDbSelForm($db1,1);?>
				</td>
			<?php else:?>
				<td></td>
			<?php endif;?>
			<?php if(!isset($_SESSION['db'][2])): ?>
				<td class="db2 conn">
					<?php echo getConnectForm(2); ?>
				</td>
			<?php elseif(!isset($_SESSION['db'][2]['db']) || $_SESSION['db'][2]['db']==''): ?>
				<td class="db2">
					 Użyj bazy danych:<?php echo getDbSelForm($db2,2);?>
				</td>
			<?php else:?>
				<td></td>
			<?php endif;?>
		</tr>
		<?php
			if($db1!==null && $db2!==null && isset($_SESSION['db'][2]['db']) && isset($_SESSION['db'][1]['db']) && $_SESSION['db'][1]['db']!='' && $_SESSION['db'][2]['db']!='')
			{
				if(!isset($_REQUEST['daneDB']))
				{
					//pola
					$tab1=$db1->getTables();
					$tab2=$db2->getTables();
					$tmArray = array();
					$pos = $character = $info = $out1 = $out2 = "";
					$equ = true;
					$count =0;
					$arrayKesy = array_unique(array_merge(array_keys($tab1),array_keys($tab2)));
					sort($arrayKesy);
					foreach($arrayKesy as $table)
					{
						$equ = true;
						$out1 = $out2 = "";
						$infoDB=false;
						if(isset($tab1[$table]) && isset($tab2[$table]))
						{
							$out1= '<span class="tabName">'.$table.'</span>';
							$out2= '<span class="tabName">'.$table.'</span>';
							$tmArray = array_diff_assoc($tab1[$table],$tab2[$table]);
							if(count($tmArray)>0)
							{
								//struktura
								if(isset($tmArray['Engine']))
								{
									$equ = false;
									 "ALTER TABLE  `$table` ENGINE =".$tab1[$table]['Engine'];
									 "ALTER TABLE  `$table` ENGINE =".$tab2[$table]['Engine'];
									$out1 .= ' <span class="Engine">'.$tab1[$table]['Engine'].'</span>';
									$out2 .= ' <span class="Engine">'.$tab2[$table]['Engine'].'</span>';
								}
								if(isset($tmArray['Collation']))
								{
									$equ = false;
									$pos = strpos($tab1[$table]['Collation'],'_');
									$character = substr($tab1[$table]['Collation'],0,$pos);
									"ALTER TABLE  `$table` DEFAULT CHARACTER SET $character COLLATE ".$tab1[$table]['Collation'];

									$pos = strpos($tab2[$table]['Collation'],'_');
									$character = substr($tab2[$table]['Collation'],0,$pos);
									"ALTER TABLE `$table` DEFAULT CHARACTER SET $character COLLATE ".$tab1[$table]['Collation'];

									$out1 .= ' <span class="Collation">'.$tab1[$table]['Collation'].'</span>';
									$out2 .= ' <span class="Collation">'.$tab2[$table]['Collation'].'</span>';
								}
								//dane
								if(isset($tmArray['Rows']))
								{
									$infoDB = true;
								}
								if(isset($tmArray['Data_length']))
								{
									$infoDB = true;
								}
							}
							$fields1 = $db1->getTableInfo($table);
							$fields2 = $db2->getTableInfo($table);
							foreach(array_unique(array_merge(array_keys($fields1),array_keys($fields2))) as $field)
							{
								if(isset($fields1[$field]) && isset($fields2[$field]))
								{
									$tmArray = array_diff_assoc(array_merge($fields1[$field], $fields2[$field]), array_intersect_assoc($fields1[$field], $fields2[$field]));
									if(count($tmArray)>0)
									{
										$equ = false;
										$out1 .= ' <span class="field">';
										$out2 .= ' <span class="field">';
										$out1 .= ' <span class="fieldName">'.$field.'</span>';
										$out2 .= ' <span class="fieldName">'.$field.'</span>';

										$pos = strpos($fields1[$field]['Collation'],'_');
										$character = substr($fields1[$field]['Collation'],0,$pos);
										"ALTER TABLE  `$table` ".
											" CHANGE  `$field`  `$field` ".$fields1[$field]['Type'].
											" CHARACTER SET $character COLLATE ".$fields1[$field]['Collation'].
											($fields1[$field]['Null']=='NO'?' NOT':'')." NULL ".
											($fields1[$field]['Default']<>''?" DEFAULT  ".$fields1[$field]['Default']:'');

										$pos = strpos($fields2[$field]['Collation'],'_');
										$character = substr($fields2[$field]['Collation'],0,$pos);
										"ALTER TABLE  `$table` ".
											" CHANGE  `$field`  `$field` ".$fields2[$field]['Type'].
											" CHARACTER SET $character COLLATE ".$fields2[$field]['Collation'].
											($fields2[$field]['Null']=='NO'?' NOT':'')." NULL ".
											($fields2[$field]['Default']<>''?" DEFAULT  ".$fields2[$field]['Default']:'');

										if($fields1[$field]['Type']<>$fields2[$field]['Type'])
										{
											$out1 .= ' Typ:<span class="fieldType">'.$fields1[$field]['Type'].'</span>';
											$out2 .= ' Typ:<span class="fieldType">'.$fields2[$field]['Type'].'</span>';
										}
										if($fields1[$field]['Null']<>$fields2[$field]['Null'])
										{
											$out1 .= ' Null:<span class="fieldNull">'.$fields1[$field]['Null'].'</span>';
											$out2 .= ' Null:<span class="fieldNull">'.$fields2[$field]['Null'].'</span>';
										}
										if($fields1[$field]['Collation']<>$fields2[$field]['Collation'])
										{
											$out1 .= ' Kodowanie: <span class="fieldCollation">'.$fields1[$field]['Collation'].'</span>';
											$out2 .= ' Kodowanie: <span class="fieldCollation">'.$fields2[$field]['Collation'].'</span>';
										}
										if($fields2[$field]['Default']<>$fields2[$field]['Default'])
										{
											$out1 .= ' Domyślnie: <span class="fieldDefault">'.$fields1[$field]['Default'].'</span>';
											$out2 .= ' Domyślnie: <span class="fieldDefault">'.$fields2[$field]['Default'].'</span>';
										}
										$out1 .= '</span>';
										$out2 .= '</span>';
									}
								}
								else
								{
									$equ = false;
									if(isset($fields1[$field]))
									{
										$out1 .= '<span class="field create">';
										$out1 .= ' <span class="fieldName">'.		$field.'</span>';
										$out1 .= ' <span class="fieldType">'.		$fields1[$field]['Type'].'</span>';
										$out1 .= ' <span class="fieldNull">'.		$fields1[$field]['Null'].'</span>';
										$out1 .= ' <span class="fieldCollation">'.	$fields1[$field]['Collation'].'</span>';
										$out1 .= ' <span class="fieldDefault">'.	$fields1[$field]['Default'].'</span>';
										$out1 .= '</span>';
									}
									if(isset($fields2[$field]))
									{
										$out2 .= '<span class="field create">';
										$out2 .= ' <span class="fieldName">'.		$field.'</span>';
										$out2 .= ' <span class="fieldType">'.		$fields2[$field]['Type'].'</span>';
										$out2 .= ' <span class="fieldNull">'.		$fields2[$field]['Null'].'</span>';
										$out2 .= ' <span class="fieldCollation">'.	$fields2[$field]['Collation'].'</span>';
										$out2 .= ' <span class="fieldDefault">'.	$fields2[$field]['Default'].'</span>';
										$out2 .= '</span>';
									}
								}
							}
							//indexy
							$keys;
							$fields = $db1->getTableIndex($table);
							foreach($fields as $field)
							{
								$fields1[$field['Key_name'].$field['Column_name']] =$field;
								$keys[$field['Key_name'].$field['Column_name']]=$field['Key_name'].$field['Column_name'];
							}
							$fields = $db2->getTableIndex($table);
							foreach($fields as $field)
							{
								$fields2[$field['Key_name'].$field['Column_name']] =$field;
								$keys[$field['Key_name'].$field['Column_name']]=$field['Key_name'].$field['Column_name'];
							}
							$index1=$index2="";
							foreach($keys as $key)
							{
								if(isset($fields1[$key]) && isset($fields2[$key]))
								{
								}
								elseif(isset($fields1[$key]))
								{
									$index1 .='<span class="field index">'.$fields1[$key]['Key_name'].' - '.$fields1[$key]['Column_name'].'</span>';
								}
								elseif(isset($fields2[$key]))
								{
									$index2 .='<span class="field index">'.$fields2[$key]['Key_name'].' - '.$fields2[$key]['Column_name'].'</span>';
								}
							}
							if($index1)
							{
								$out1.='<div>Index:'.$index1.'</div>';
							}
							if($index2)
							{
								$out2.='<div>Index:'.$index2.'</div>';
							}
							//unset($keys,$fields,$index1,$index2);
							if($equ)
							{
								if(!$onlyDiff)
								{
									echo
									'<tr class="equ row_'.(($count++)%2).'" ><td class="toCmpCont"><a '.($infoDB?'class="warning" ':'').'href="?daneDB='.$table.'" title="Porównaj dane w tabelach">+</a></td><td class="db1">'.$table.'</td><td class="db2">'.$table.'</td></tr>';
								}
							}
							else
							{
								echo
								'<tr class="notEqu row_'.(($count++)%2).'" ><td class="toCmpCont"><a '.($infoDB?'class="warning" ':'').'href="?daneDB='.$table.'"title="Porównaj dane w tabelach">+</a></td><td class="db1">'.$out1.'</td><td class="db2">'.$out2.'</td></tr>';
							}
						}
						else
						{
							$que="";
							if(isset($tab1[$table]))
							{
								$que = $db1->ask("show create table ".$table." ");
								$que =$que[0];
								echo '<tr class="notEqu row_'.(($count++)%2).'" ><td class="toCmpCont"></td><td class="db1">'.$table.'</td><td class="db2"></td></tr>';
							}
							elseif(isset($tab2[$table]))
							{
								$que = $db2->ask("show create table ".$table." ");
								$que =$que[0];
								echo '<tr class="notEqu row_'.(($count++)%2).'" ><td class="toCmpCont"></td><td class="db1"></td><td class="db2">'.$table.'</td></tr>';
							}
						}
					}
				}
				elseif(isset($_GET['daneDB']))
				{
					echo '<tr><td colspan="3" id="workArea">';
					$table = mysql_escape_string($_GET['daneDB']);
					$tab1=$db1->getTables();
					$tab2=$db2->getTables();
					$tabToCmp = array_intersect(array_keys($tab1),array_keys($tab2));
					if(in_array($table,$tabToCmp))
					{
						$tab1 = $db1->getTableInfo($table);
						$tab2 = $db2->getTableInfo($table);
						$fieldsToCmp = array_intersect(array_keys($tab1),array_keys($tab2));
						?>
						<form method="post" action="?">
							<input type="hidden" name="daneDB" value="<?php echo $table ?>">
							<div class="dbName">Porównujesz dane z tabelii <i><?php echo $table ?></i></div>
							<div id="prpareCmpCont">
								<div class="cpData">
									<div>
										<div class="cmpConName">Porównaj na podstawie pól</div>
										<div class="cpBy">
											<?php
												foreach($fieldsToCmp as $field)
												{
													echo '<label for="cpBy'.$field.'"><input type="checkbox" id="cpBy'.$field.'" name="cpBy['.$field.']"/><span class="fieldName">'.$field.'</span> '.(isset($tab1[$field])?$tab1[$field]['Type']:$tab2[$field]['Type']).' '.(isset($tab1[$field]) && $tab1[$field]['Comment']<>''?' //1'.$tab1[$field]['Comment']:'').(isset($tab2[$field]) && $tab2[$field]['Comment']<>''?' //2'.$tab2[$field]['Comment']:'').'</label>';
												}
											?>
										</div>
									</div>
									<div>
										<div class="cmpConName">Pokaż pola</div>
										<div class="cpSh">
										<?php
											foreach($fieldsToCmp as $field)
											{
												echo '<label for="sh'.$field.'"><input type="checkbox" id="sh'.$field.'" name="sh['.$field.']"/><span class="fieldName">'.$field.'</span> '.(isset($tab1[$field])?$tab1[$field]['Type']:$tab2[$field]['Type']).' '.(isset($tab1[$field]) && $tab1[$field]['Comment']<>''?' //1'.$tab1[$field]['Comment']:'').(isset($tab2[$field]) && $tab2[$field]['Comment']<>''?' //2'.$tab2[$field]['Comment']:'').'</label>';
											}
										?>
										</div>
									</div>
									<div>
										<div class="cmpConName">Sortuj według pól</div>
										<div class="cpOrder">
										<?php
											$sel = "<option value=\"-1\">--</option>";
											foreach($fieldsToCmp as $field)
											{
												$sel .=  '<option value="'.$field.'">'.$field.'</option>';
											}
											for($i=(count($fieldsToCmp)/2);$i>0;$i--)
											{
												echo '<select name="ordBy[]">'.$sel.'</select> <br />';
											}
										?>
										</div>
									</div>
								</div>
							</div>
							<input type="submit" >
						</form>
						<?php
					}
					else
					{
						echo '<div class="warning">Wybrana tabela nie występuje na <a href="?">liście</a></div>';
					}
					echo '</td></tr>';
				}
				elseif(isset($_POST['daneDB']))
				{
					$table = mysql_escape_string($_POST['daneDB']);
					$tab1=$db1->getTables();
					$tab2=$db2->getTables();
					$tabToCmp = array_intersect(array_keys($tab1),array_keys($tab2));
					if(in_array($table,$tabToCmp))
					{
						echo '</table>';
						echo '<div class="dbName">Powróć do wyborów porównania <a href = "?daneDB='.$_POST['daneDB'].'">'.$_POST['daneDB'].'</a></div>';
						$cpBy=array();
						if(isset($_POST['cpBy']))
						{
							$cpBy = array_keys($_POST['cpBy']);
						}
						if(count($cpBy)>0)
						{
							$sh=array();
							if(isset($_POST['sh']))
							{
								$sh = array_keys($_POST['sh']);
							}
							$ordBy = array();
							if(isset($_POST['ordBy']))
							{
								foreach($_POST['ordBy'] as $ordByF=>$val)
								{
									if(!is_numeric($ordByF))
									{
										$ordBy[]=mysql_escape($ordByF);
									}
								}
							}
							$table = mysql_escape_string($_POST['daneDB']);
							$tab1 = $db1->getTableInfo($table);
							$tab2 = $db2->getTableInfo($table);
							$fieldsToCmp = array_intersect(array_keys($tab1),array_keys($tab2));
							$go = (count($cpBy)>0);
							for($i = count($cpBy)-1;$i>=0 && $go; $i--  )
							{
								if(!in_array($cpBy[$i],$fieldsToCmp))
								{
									$go = false;
								}
							}
							if(count($sh)>0)
							{
								for($i = count($sh)-1;$i>=0 && $go; $i--  )
								{
									if(!in_array($sh[$i],$fieldsToCmp))
									{
										$go = false;
									}
								}
							}
							if(count($sh)>0)
							{
								for($i = count($ordBy)-1;$i>=0 && $go; $i--  )
								{
									if($ordBy[$i]==-1)
									{
										unset($ordBy[$i]);
									}
									elseif(!in_array($ordBy[$i],$fieldsToCmp))
									{
										$go = false;
									}
								}
							}
							if($go)
							{

								$show = 'table';
								if($show == 'tree')
								{
									$sql = 'SELECT '.implode(',',$cpBy+$sh).' FROM '.$table.' ORDER BY '.implode(',',$ordBy);
									$tab1 = $db1->ask($sql);
									$tab2 = $db2->ask($sql);
									if($tab1->rowsCount==0 || $tab2->rowsCount==0)
									{
										$db=($tab1->rowsCount==0?1:2);
										echo '<div class="dbName">Tabela '.$table.' z bazy '.$_SESSION['db'][$db]['user'].'@'.$_SESSION['db'][$db]['host'].' u:'.$_SESSION['db'][$db]['db'].' jest <strong>pusta</strong> </div>';
										return;
									}
									$tmCmp = array();
									$toEval1 = '$tmCmp[$val[\''.implode('\']][$val[\'',$cpBy).'\']]';
									foreach($sh as $tm )
									$toEval2[]= '\''.$tm.'\'=>$val[\''.$tm.'\']';
									$toEval2 = '=array('.implode(',',$toEval2).');';
									$toEvalAll = $toEval1.'[0]'.$toEval2;
									foreach($tab1 as $val)
									{
										eval($toEvalAll);
									}
									$toEvalAll = $toEval1.'[1]'.$toEval2;
									foreach($tab2 as $val)
									{
										eval($toEvalAll);
									}
									function drawCmpTab($tmCmp,$cpBy,$sh,$level=0)
									{
										if(count($cpBy)>$level)
										{
											$out = '<div class="lv_'.$level.'">';
													'<div class="name">'.$cpBy[$level-1].'</div>';
											foreach($tmCmp as $key=>$val)
											{
												$out.='<table><tr><td>'.$cpBy[$level].'</td><td></td></tr><tr><td>'.$key.'</td><td>'.drawCmpTab($val,$cpBy,$sh,$level+1).'</td></tr></table>';
											}
											$out.='</div>';
											return $out;
										}
										else
										{
											if($db = (isset($tmCmp[0])?0:(isset($tmCmp[1])?1:-1))==-1) return '----';
											$th='<td></td>';
											$db0='<td>db1</td>';
											$db1='<td>db2</td>';
											foreach($sh as $pole)
											{
												$th.='<td>'.$pole.'</td>';
												$db0.='<td>'.(isset($tmCmp[0][$pole])?$tmCmp[0][$pole]:'--').'</td>';
												$db1.='<td>'.(isset($tmCmp[1][$pole])?$tmCmp[1][$pole]:'--').'</td>';
											}
											return '<table><tr>'.$th.'</tr><tr>'.$db0.'</tr><tr>'.$db1.'</tr></table>';
										}
									}
									echo '<div id="tm">', drawCmpTab($tmCmp,$cpBy),'</div><style>#tm div {border:1px solid red;} td{border:1px solid black}</style>';
								}
								elseif($show == 'table')
								{
									$shCp = array_unique(array_merge($cpBy,$sh));
									$sql = 'SELECT '.implode(',',$shCp).', (concat('.implode(',":",',$cpBy).')) as "toCmp"   FROM '.$table.' ORDER BY '.implode(',',(count($ordBy)>0?array_unique(array_merge($cpBy,$ordBy)):$cpBy) );
									$tab1 = $db1->ask($sql,true,true,'toCmp');
									$tab2 = $db2->ask($sql,true,true,'toCmp');
									$keys = array_unique(array_merge(array_keys($tab1),array_keys($tab2)));
									$count = 0;
									sort($keys);
									$out = "<tr class=\"cmpConHead\">";
									$feq=$isSet1=$isSet2=$eq="";
									$toEval2=array();
									foreach($shCp as $tm )
									{
										$toEval2[]='"<td class=\"'.$tm.' db1".($eq=((($isSet1=(isset($tab1[$uField]) && isset($tab1[$uField][\''.$tm.'\'])))& ($isSet2=(isset($tab2[$uField]) && isset($tab2[$uField][\''.$tm.'\']))))&&($tab1[$uField][\''.$tm.'\'] == $tab2[$uField][\''.$tm.'\']))?"":" notEqu")."\">".($isSet1?$tab1[$uField][\''.$tm.'\']:"--")."</td><td class=\"'.$tm.' db2$eq\">".($isSet2?$tab2[$uField][\''.$tm.'\']:"--")."</td>" ';
										$out .= "<td>$tm [1]</td><td>$tm [2]</td>";
									}
									$evalDisplay = '$out.="<tr class=\"row_".($count++%2)."\">".'.implode('.',$toEval2).'."</tr>";';
									$out .="</tr>";
									foreach($keys as $uField)
									{
										if(isset($tab1[$uField]) && isset($tab2[$uField]) && $tab1[$uField]==$tab2[$uField] )
										{
											if(!$onlyDiff)
											{
												eval($evalDisplay);
											}
										}
										else
										{
											eval($evalDisplay);
										}
									}
									echo '<table style="margin:0 auto;">',$out,'</table>' ;
								}
							}
						}
						elseif(count($cpBy)==0)
						{
							echo '<div class="warning" style="text-align:center;" >Musisz wybrać conajmniej jedno pole po którym dane będą porównywane.</div>';
						}
					}
					else
					{
						echo '</table>';
						echo '<div class="warrning">Wybrana tabela nie występuje na <a href="?">liście</a></div>';
					}
				}
			}
		?>
	</body>
</html>
<?php // pozostałe funkcje :D
function getDbSelForm($db,$id)
{
	$ret="";
	$db->getDatabases();
	foreach($db->result as $row )
	{
		if(strtolower($row[0]) =='information_schema')continue;
		$ret .=  '<option value="'.$row[0].'">'.$row[0].'</option>';
	}
	return'<form action="?" method="post"><select name="useDB" >'. $ret .'</select><input type="submit" name="useDB'.$id.'" ></form>';
}
function getConnectForm($idConn)
{
	return<<<html
<form action="?" method="post"> <table> <tr> <td>User</td> <td>@</td> <td>Host</td> <td>:</td> <td>Port</td> </tr> <tr> <td><input name="user" type="text" /></td> <td>@</td> <td><input name="host" type="text" /></td> <td>:</td> <td><input name="port" type="text" size="4" value="3306"/></td> </tr> <tr> <td>Password</td> <td></td> <td></td> <td></td> <td></td> </tr> <tr> <td><input  name="pass" type="password"/></td> <td></td> <td></td> <td></td> <td></td> </tr> <tr> <td></td> <td></td> <td></td> <td></td> <td><input type="submit" name="db$idConn" value="Connent" /></td> </table> </form>
html;
}
?>
