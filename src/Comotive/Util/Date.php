<?php

namespace Comotive\Util;

/**
 * Klasse die verschiedene Datumsoperationen zur Verfügung stellt.
 * Dazu gehören Check und EU / SQL Konvertierungsfunktionen sowie
 * Konstanten für Formatierungen und Reguläre ausdrücke
 * @author Michael Sebel <michael@comotive.ch>
 */
class Date
{
	/**
	 * Formattyp SQL Datetime
	 * @var string
	 */
	const SQL_DATETIME 	= 'Y-m-d H:i:s';
	/**
	 * Formattyp SQL Date
	 * @var string
	 */
	const SQL_DATE  	= 'Y-m-d';
	/**
	 * Formattyp EU Datetime
	 * @var string
	 */
	const EU_DATETIME 	= 'd.m.Y H:i:s';
	/**
	 * Formattyp EU Date
	 * @var string
	 */
	const EU_DATE		= 'd.m.Y';
	/**
	 * Formattyp EU Zeit (inkl. Sekunden)
	 * @var string
	 */
	const EU_TIME		= 'H:i:s';
	/**
	 * Formattyp EU Weckeranzeige (exkl. Sekunden)
	 * @var string
	 */
	const EU_CLOCK		= 'H:i';
	/**
	 * Formattyp für ein korrektes Datum nach RFC 822
	 * @var string
	 */
	const RFC822_DATE		= 'D, d M Y H:i:s e';
	/**
	 * Regulärer Ausdruck für Checks auf Datum (dd.mm.yyyy)
	 * @var string
	 */
	const EU_FORMAT_DATE		= '/^(([012][0-9])|(3[01])).((0[1-9])|(1[0-2])).(((19[0-9])|(20[0-9]))[0-9])$/';
	/**
	 * Regulärer Ausdruck für Checks auf Zeit (hh:mm:ss)
	 * @var string
	 */
	const EU_FORMAT_TIME		= '/^(([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1})[:]{1}[0-5]{1}[0-9]{1}[:]{1}[0-5]{1}[0-9]{1})$/';
	/**
	 * Regulärer Ausdruck für Checks auf Datum (dd.mm.yyyy hh:mm:ss)
	 * @var string
	 */
	const EU_FORMAT_DATETIME	= '/^(([012][0-9])|(3[01])).((0[1-9])|(1[0-2])).(((19[0-9])|(20[0-9]))[0-9]) (([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1})[:]{1}[0-5]{1}[0-9]{1}[:]{1}[0-5]{1}[0-9]{1})$/';
	/**
	 * Regulärer Ausdruck für Checks auf SQL Datum (dd-mm-yyyy)
	 * @var string
	 */
	const SQL_FORMAT_DATE		= '/^(((19[0-9])|(20[0-9]))[0-9])-((0[1-9])|(1[0-2]))-(([012][0-9])|(3[01]))$/';
	/**
	 * Regulärer Ausdruck für Checks auf SQL Zeit (hh:mm:ss)
	 * @var string
	 */
	const SQL_FORMAT_TIME		= '/^(([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1})[:]{1}[0-5]{1}[0-9]{1}[:]{1}[0-5]{1}[0-9]{1})$/';
	/**
	 * Regulärer Ausdruck für Checks auf SQL Datum (dd-mm-yyyy hh:mm:ss)
	 * @var string
	 */
	const SQL_FORMAT_DATETIME 	= '/^(((19[0-9])|(20[0-9]))[0-9])-((0[1-9])|(1[0-2]))-(([012][0-9])|(3[01])) (([0-1]{1}[0-9]{1}|[2]{1}[0-3]{1})[:]{1}[0-5]{1}[0-9]{1}[:]{1}[0-5]{1}[0-9]{1})$/';
	
	/**
	 * Validiert Zeit eingaben.
	 * Nimmt einen Wert und gibt Ihn zurück. Es kommt die aktuelle
	 * Zeit, wenn ein ungültiger Wert (NULL) daher kommt.
	 * @param integer vTime, eingegebene Zeit
	 * @return integer Aktuelle Zeit, wenn gegebene ungültig
	 */
	private static function setTime($vTime)
  {
		$nTime = time();
		// Gegebenen Stamp nutzen wenn vorhanden
		if ($vTime != NULL) $nTime = $vTime;
		return($nTime);
	}
	
	/**
	 * Formatiert einen Timestamp oder aktuelle Zeit anhand $sFormat
	 * @param string sFormat, Formatierung für date Funktion
	 * @param integer vTime, Timestamp darf auch NULL sein für aktuelle Zeit
	 * @return string Formatiertes Datum
	 */
	public static function getTime($sFormat,$vTime = NULL)
  {
		$nTime = self::setTime($vTime);
		$sDate = date($sFormat,$nTime);
		return($sDate);
	}
	
	/**
	 * Formatiert das Datum $sDate von $sFrom nach $sTo.
	 * @param string sFrom, Eingangsformat
	 * @param string sTo, Ausgangsformat
	 * @param string sDate, zu konvertierendes Datum im Eingangsformat
	 * @return string Konvertiertes Datum im Ausgangsformat
	 */
	public static function convertDate($sFrom,$sTo,$sDate)
  {
		// Mit From einen Stempfel holen
		$nStamp = self::getStamp($sFrom,$sDate);
		// Stempfel in To konvertieren
		$sNewDate = self::getTime($sTo,$nStamp);
		return($sNewDate);
	}
	
	/**
	 * Gibt anhand der Formatierung den Timestamp eines Datums zurück
	 * @param string sFormat, Formatierung des eingegebenen Datum
	 * @param string sDate, Zu konvertierendes Datum
	 * @return integer Timestamp des gegebenen Datums
	 */
	public static function getStamp($sFormat,$sDate)
  {
		$nStamp = 0;
		switch ($sFormat) {
			// SQL Datumsstring yyyy-mm-dd
			case (self::SQL_DATE):
				// Datumseinheiten extrahieren
				$nYear = substr($sDate,0,4);
				$nMonth = substr($sDate,5,2);
				$nDay = substr($sDate,8,2);
				// Stamp generieren
				$nStamp = mktime(0,0,0,$nMonth,$nDay,$nYear);
				break;
			// SQL Datum/Zeit String yyyy-mm-dd hh:mm:ss
			case (self::SQL_DATETIME):
				// Datumseinheiten extrahieren
				$nYear = substr($sDate,0,4);
				$nMonth = substr($sDate,5,2);
				$nDay = substr($sDate,8,2);
				$nHour = substr($sDate,11,2);
				$nMinute = substr($sDate,14,2);
				$nSecond = substr($sDate,17,2);
				// Stamp generieren
				$nStamp = mktime($nHour,$nMinute,$nSecond,$nMonth,$nDay,$nYear);
				break;
			// Europäischer Datumsstring dd.mm.yyyy
			case (self::EU_DATE):
				$nDay = substr($sDate,0,2);
				$nMonth = substr($sDate,3,2);
				$nYear = substr($sDate,6,4);
				// Stamp generieren
				$nStamp = mktime(0,0,0,$nMonth,$nDay,$nYear);
				break;
			// Europäisches Datum/Zeit dd.mm.yyyy, hh:mm:ss
			case (self::EU_DATETIME):
				$nDay = substr($sDate,0,2);
				$nMonth = substr($sDate,3,2);
				$nYear = substr($sDate,6,4);
				$nHour = substr($sDate,11,2);
				$nMinute = substr($sDate,14,2);
				$nSecond = substr($sDate,17,2);
				// Stamp generieren
				$nStamp = mktime($nHour,$nMinute,$nSecond,$nMonth,$nDay,$nYear);
				break;
		}
		// Resultat zurückgeben
		return($nStamp);
	}
	
	/**
	 * Tag des Jahres als Zahl, nicht nullbasiert.
	 * @param integer nStamp, Zeitstempfel dessen Tag herausgefunden werden muss
	 * @return integer Tag des Jahres 1 - 365 (366)
	 */
	public static function getDayOfYear($nStamp)
  {
		$nStamp = getInt($nStamp);
		$nRet = (date("z",$nStamp))+1;
		return($nRet);
	}
	
	/**
	 * Tag der Woche als Zahl, nicht nullbasiert.
	 * @param integer nStamp, Zeitstempfel dessen Tag herausgefunden werden muss
	 * @return integer Tag der Woche, 1 = Montag, 7 = Sonntag
	 */
	public static function getDayOfWeekNumeric($nStamp)
  {
		$nStamp = getInt($nStamp);
		$nRet = date("w",$nStamp);
		if ($nRet == 0) $nRet = 7;
		return($nRet);
	}
	
	/**
	 * Mehrsprachiger Wochentag eines Timestamps.
	 * @param integer nStamp, zu übergebenes Timestamp
	 * @param resources Res, Sprachobjekt
	 * @return string Wochentag als String (lang)
	 */
	public static function getDayOfWeek($nStamp,resources &$Res)
  {
		$nStamp = getInt($nStamp);
		$nRet = date("w",$nStamp);
		if ($nRet == 0) $nRet = 7;
		$sDay = self::getWeekday($nRet,$Res);
		return($sDay);
	}
	
	/**
	 * Mehrsprachiger Wochentag eines Timestamps.
	 * @param integer nStamp, zu übergebenes Timestamp
	 * @param resources Res, Sprachobjekt
	 * @return string Wochentag als String (kurz)
	 */
	public static function getDayOfWeekShort($nStamp,resources &$Res)
  {
		$nStamp = getInt($nStamp);
		$nRet = date("w",$nStamp);
		if ($nRet == 0) $nRet = 7;
		$sDay = self::getWeekdayShort($nRet,$Res);
		return($sDay);
	}
	
	/**
	 * Anhand des Wochentages (1-7) dessen langer Name holen.
	 * @param integer nWeekday, Wochentag 1 (Montag) - 7 (Sonntag)
	 * @param resources Res, Sprachobjekt
	 * @return string Übersetzter Wochentag
	 */
	public static function getWeekday($nWeekday,resources &$Res)
  {
		// Switchen und Resourcen holen
		switch ($nWeekday) {
			case 1:	$sDay = $Res->html(27,page::language()); break;
			case 2:	$sDay = $Res->html(28,page::language()); break;
			case 3:	$sDay = $Res->html(29,page::language()); break;
			case 4:	$sDay = $Res->html(30,page::language()); break;
			case 5:	$sDay = $Res->html(31,page::language()); break;
			case 6:	$sDay = $Res->html(32,page::language()); break;
			case 7:	$sDay = $Res->html(33,page::language()); break;
		}
		// Wochentag zurückgeben
		return($sDay);
	}
	
	/**
	 * Anhand des Wochentages (1-7) dessen Abkürzung holen.
	 * @param integer nWeekday, Wochentag 1 (Montag) - 7 (Sonntag)
	 * @param resources Res, Sprachobjekt
	 * @return string Übersetzter Wochentag
	 */
	public static function getWeekdayShort($nWeekday,resources &$Res)
  {
		// Switchen und Resourcen holen
		switch ($nWeekday) {
			case 1:	$sDay = $Res->html(571,page::language()); break;
			case 2:	$sDay = $Res->html(572,page::language()); break;
			case 3:	$sDay = $Res->html(573,page::language()); break;
			case 4:	$sDay = $Res->html(574,page::language()); break;
			case 5:	$sDay = $Res->html(575,page::language()); break;
			case 6:	$sDay = $Res->html(576,page::language()); break;
			case 7:	$sDay = $Res->html(577,page::language()); break;
		}
		// Wochentag zurückgeben
		return($sDay);
	}
	
	/**
	 * Mehrsprachigen Monat (langer Name) zurückgeben
	 * @param integer nMonth, Monatsnummer 1 - 12
	 * @param resources Res, Sprachobjekt
	 * @return string Name des gewünschten Monats
	 */
	public static function getMonthName($nMonth,resources &$Res)
  {
		// Switchen und Resourcen holen
		switch ($nMonth) {
			case  1: $sMonth = $Res->html(582,page::language()); break;
			case  2: $sMonth = $Res->html(583,page::language()); break;
			case  3: $sMonth = $Res->html(584,page::language()); break;
			case  4: $sMonth = $Res->html(585,page::language()); break;
			case  5: $sMonth = $Res->html(586,page::language()); break;
			case  6: $sMonth = $Res->html(587,page::language()); break;
			case  7: $sMonth = $Res->html(588,page::language()); break;
			case  8: $sMonth = $Res->html(589,page::language()); break;
			case  9: $sMonth = $Res->html(590,page::language()); break;
			case 10: $sMonth = $Res->html(591,page::language()); break;
			case 11: $sMonth = $Res->html(592,page::language()); break;
			case 12: $sMonth = $Res->html(593,page::language()); break;
		}
		// Wochentag zurückgeben
		return($sMonth);
	}

	/**
	 * Verwandelt ein SQL Date in eine Human Readable Date
	 * in der Form "13.03.2010 / 10:00 Uhr"
	 * @param string $sSqlDate SQL Datetime String
	 * @return string Human Readable Datum/Zeit
	 */
	public static function toHumanReadable($sSqlDate)
  {
		$sDate = Date::convertDate(
			self::SQL_DATETIME,
			self::EU_DATE,
			$sSqlDate
		);
		$sTime = Date::convertDate(
			self::SQL_DATETIME,
			self::EU_TIME,
			$sSqlDate
		);
		$sClock = __('Uhr', 'lbwp');
		// So zusammenführen
		return($sDate.' / '.$sTime.' '.$sClock);
	}
}