#!/usr/bin/php
<?php
// vim: foldmethod=marker tabstop=4 shiftwidth=4

include("mobile-detect.php");

// extend for isPhone() check
class MobDetect extends Mobile_Detect
{
    public function isPhone($userAgent = null, $httpHeaders = null)
    {
        $this->setDetectionType(self::DETECTION_TYPE_MOBILE);

        foreach (self::$phoneDevices as $_regex) {
            if ($this->match($_regex, $userAgent)) {
                return true;
            }
        }

        return false;
    }
}

function checkmobile($useragent,$description,$type) {
	$ch = curl_init();
	$curlConfig = array(
	    CURLOPT_URL            => "http://localhost/",
	    // CURLOPT_POST           => true,
	    CURLOPT_HEADER         => true,
	    CURLOPT_CUSTOMREQUEST  => $type, 
	    CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERAGENT	   => $useragent,
	);
	curl_setopt_array($ch, $curlConfig);
	$result = curl_exec($ch);
	
	$Device="";
	$OS="";
	$Browser="";
	$Type="";
	$Detail="";
	$isMobile="";
	if (preg_match("/X-Varnish-UA-Device: (.+)/",$result,$arr)) $Device=trim($arr[1]);
	if (preg_match("/X-Varnish-UA-OS: (.+)/",$result,$arr))     $OS=trim($arr[1]);
	if (preg_match("/X-Varnish-UA-Type: (.+)/",$result,$arr))   $Type=trim($arr[1]);
	if (preg_match("/X-Varnish-UA-Detail: (.+)/",$result,$arr))   $Detail=trim($arr[1]);
	if (preg_match("/X-Varnish-UA-isMobile: (.+)/",$result,$arr))   $isMobile=trim($arr[1]);
	if (preg_match("/X-Varnish-UA-Browser: (.+)/",$result,$arr))   $Browser=trim($arr[1]);
	// print "Description: $description\n"; 
	// print "User-Agent: $useragent\n"; 
	// print $result;
	// print_r($arr);
	switch ($type) {
		case "MOBILEDETECT": 
					$t="MD"; 
					break;
		case "DEVICEDETECT": 
					$t="DD"; 
					break;
	}
	// printf("$t:M:%s\tD:%s\tO:%s\tT:%s\tD:%s\t%s\t%s\n",$isMobile,$Device,$OS,$Type,$Detail,$description,$useragent);
	$varnish=sprintf("%s\t%s\t%s\t%s\t%s\t%s\t%s\n",$isMobile,$Device,$OS,$Type,$Detail,$description,$useragent);

	$Device="pc";
	$OS="";
	$Type="";
	$Detail="";
	$isMobile="";

	$detect= new MobDetect;

	if ($detect->isMobile($useragent)) $isMobile="yes";

	if ($detect->isPhone($useragent)) {
		// print "checkin Phones...\n";
		$Type="Phone";
		foreach ($detect->getPhoneDevices() as $key => $val) {
			if ($detect->is($key)) {
				$Detail=$key;
				// print "gefunden!!! $key\n";
			}
		}
		if ("$Device" == "pc") $Device="Sonstiges";
	} elseif ($detect->isTablet($useragent)) {
		// print "checkin Tablets...\n";
		$Type="Tablet";
		foreach ($detect->getTabletDevices() as $key => $val) {
			if ($detect->is($key)) {
				$Detail=$key;
				// print "gefunden!!! $key\n";
			}
		}
		if ("$Device" == "pc") $Device="Sonstiges";
	}

	if ($detect->isAndroidOS($useragent))       { $OS="Android"; $Device=$OS; }
	if ($detect->isSymbianOS($useragent))       { $OS="Symbian"; $Device=$OS; }
	if ($detect->isiOS($useragent))             { $OS="iOS"; $Device=$OS; }
	if ($detect->isWindowsPhoneOS($useragent))  { $OS="Windows Phone"; $Device=$OS; }

	if ($detect->isWindowsMobileOS($useragent)) { $OS="WindowsCE"; $Device="Sonstiges"; }
	if ($detect->isBlackberryOS($useragent)) { $OS="Blackberry"; $Device="Sonstiges"; }
	if ($detect->isMeeGoOS($useragent))      { $OS="Meego"; $Device="Sonstiges"; }
	if ($detect->isMaemoOS($useragent))      { $OS="Maemo"; $Device="Sonstiges"; }
	if ($detect->isJavaOS($useragent))       { $OS="JavaOS"; $Device="Sonstiges"; }
	if ($detect->iswebOS($useragent))        { $OS="webOS"; $Device="Sonstiges"; }
	if ($detect->isbadaOS($useragent))       { $OS="bada"; $Device="Sonstiges"; }
	if ($detect->isPalmOS($useragent))       { $OS="Palm"; $Device="Sonstiges"; }
	if ($detect->isBREWOS($useragent))       { $OS="BREW"; $Device="Sonstiges"; }


	$php=sprintf("%s\t%s\t%s\t%s\t%s\t%s\t%s\n",$isMobile,$Device,$OS,$Type,$Detail,$description,$useragent);
	if ($varnish != $php) {
		print "FEHLER!\n";
		print "V:".$varnish;
		print "P:".$php;
		print "B:".$Browser;
	}


// print $result;
	curl_close($ch);
}

$useragents=array();
$i=0;
function xml2assoc($xml) { 
    global $useragents;
	global $i;
    $tree = null; 
    while($xml->read()) 
        switch ($xml->nodeType) { 
            case XMLReader::END_ELEMENT: return $tree; 
            case XMLReader::ELEMENT: 
                $node = array('tag' => $xml->name, 'value' => $xml->isEmptyElement ? '' : xml2assoc($xml)); 
                if($xml->hasAttributes) 
                    while($xml->moveToNextAttribute()) {
                        $node['attributes'][$xml->name] = $xml->value; 
						if ($xml->name=="description") $description=$xml->value; 
						if ($xml->name=="useragent") { 
							$useragent=$xml->value; 
							$useragents[$i]['description']=$description;
							$useragents[$i]['useragent']=$useragent;
							$i++;
						}
					}
                $tree[] = $node; 
            break; 
            case XMLReader::TEXT: 
            case XMLReader::CDATA: 
                $tree .= $xml->value; 
        } 
    return $tree; 
} 


$xml = new XMLReader(); 
#$xml->open('useragentswitcher.xml'); 
$xml->open('useragentswitchertest.xml'); 
$assoc = xml2assoc($xml, "root"); 
$xml->close();

// $arr=($assoc[0]['value'][0]['value']);
//print_r($useragents);
foreach ($useragents as $arr) {
	if ($arr['useragent'] != '') {
#		checkmobile($arr['useragent'],$arr['description'],"DEVICEDETECT");
		checkmobile($arr['useragent'],$arr['description'],"MOBILEDETECT");
	}

}

?> 
