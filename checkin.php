<?php
include 'config.inc';
include 'common.inc';
$shost = $cloudhost;
$colid = getlocalid();
$ret1 = "";
$ret2 = "";

$apiurl = "http://$shost/dashboard/api/v0/checkin/?collector=$colid&rc=1";

doregloop($dbtype,$dbserver,$dbname,$dbun,$dbpw,$colid,$apiurl);

function doregloop($dbt,$dbs,$dbn,$dbu,$dbp,$colid,$apiurl) {
        $x = 0;
        do {
                $x++;
		DoCheckin($dbt,$dbs,$dbn,$dbu,$dbp,$colid,$apiurl);
                sleep(30);
        } while ($x >= 0);
}

function DoCheckin($dbt,$dbs,$dbn,$dbu,$dbp,$colid,$apiurl) {
  $arr_ret = DoGet($apiurl,"","");
  $ret = $arr_ret[0];
  $ret1 = "";
  $ret2 = "";

  echo "------------\n$ret\n----------------\n";
  $arr_ret = json_decode($ret,true);

  $res = $arr_ret["dashboard"]["response"];
  if($res=="success") {
    $acount = $arr_ret["dashboard"]["action_count"];
    for($x=0;$x<$acount;$x++) {
      $xd = $x+1;
      $taskpkid = $arr_ret["dashboard"]["action"]["a$xd"]["taskpkid"];
      $meth = $arr_ret["dashboard"]["action"]["a$xd"]["method"];
      $url = base64_decode($arr_ret["dashboard"]["action"]["a$xd"]["url"]);
      $url = str_replace(" ", "%20", $url);
      $ctype = base64_decode($arr_ret["dashboard"]["action"]["a$xd"]["content_type"]);
      $soapact = base64_decode($arr_ret["dashboard"]["action"]["a$xd"]["soap_action"]);
      $creds = $arr_ret["dashboard"]["action"]["a$xd"]["creds"];
      $cookie = base64_decode($arr_ret["dashboard"]["action"]["a$xd"]["cookie"]);
      $msg = base64_decode($arr_ret["dashboard"]["action"]["a$xd"]["msg"]);
      $rdata = base64_decode($arr_ret["dashboard"]["action"]["a$xd"]["ret"]);
      $filekey = base64_decode($arr_ret["dashboard"]["action"]["a$xd"]["filepair"]);
      $msg = str_replace("\\\"","\"",$msg);
      if($ret1!="") {
        $msg = str_replace("%ret1%",$ret1,$msg);
        $cookie = str_replace("%ret1%",$ret1,$cookie);
      }

      echo "Num=$xd\nMethod=$meth\nURL=$url\nContent-Type=$ctype\nSOAP-Action=$soapact\nCredentials=$creds\nCookie=$cookie\nMessage=$msg\nReturn=$rdata\nFile Pair=$filekey\n---------\n";
      if(($meth=="POST") || ($meth=="GET") || ($meth=="DELETE")) {
        if(1==1) {
          if($meth=="POST") {
	    if($filekey!="") {
	      $arr_opres = DoPostUpload($url,$msg,$cookie,$ctype,$soapact,$creds,$filekey);
echo("~~~~~~~~~~~~~~~~~~~~~~~" . $arr_opres[1] . "~~~~~~~~~~~~~~~~~~~~~~");
	    } else {
              $arr_opres = DoPost($url,$msg,$cookie,$ctype,$soapact,$creds);
	    }
            $opres = $arr_opres[0];
          } else if($meth=="GET") {
            $arr_opres = DoGet($url,$creds,$cookie);
            $opres = $arr_opres[0];
	  } else if($meth=="DELETE") {
	    $arr_opres = DoDelete($url,$creds,"");
	    $opres = $arr_opres[0];
echo("~~~~~~~~~~~~~~~~~~~~~~~" . $arr_opres[1] . "~~~~~~~~~~~~~~~~~~~~~~");
          }
          if(($rdata!="") && ($rdata!="*")) {
            //XML=aaaLogin;attributes,outCookie->ret1
            $arr_rdata = explode("=",$rdata);
            if($arr_rdata[0]=="XML") {
              $arr_rdata2 = explode("-)",$arr_rdata[1]);
              $arr_rdata3 = explode(";",$arr_rdata2[0]);
              $rtag = $arr_rdata3[0];
              $rval = $arr_rdata3[1];
              if($arr_rdata2[1]=="ret1") { $ret1 = getXmlValueByTag($opres,$rtag,$rval,1); }
            } else if($arr_rdata[0]=="JSON") {
              //@@ Incomplete
              $arr_rdata2 = explode("->",$arr_rdata[1]);

              $arr_opres = json_decode($opres, true);
              $token = $arr_opres["imdata"]["0"]["aaaLogin"]["attributes"]["token"];
              if($arr_rdata2[1]=="ret1") { $ret1 = $token; } 
            } else if($arr_rdata[0]=="COOKIE") {
              $arr_rdata2 = explode("-)",$arr_rdata[1]);
              $arr_cval = ExtractCookie($arr_opres[1],$arr_rdata2[0]);
              $cval = $arr_cval[0];
              echo "********\n$cval\n*************\n" . $arr_rdata2[0] . "\n********\n" . $arr_opres[1] . "\n***********";
              if($arr_rdata2[1]=="ret1") { $ret1 = $cval; }
            }
          }
          echo "$msg\n--------------\n$opres\n-------------\n$ret1\n===========\n";
        }
      }
      if($rdata=="*") {
        if($opres!="") {
          $arr_reqret = array("collector"=>array("id"=>$colid));
          $arr_reqret["checkin"]["taskresult"]["pkid"] = $taskpkid;
          $arr_reqret["checkin"]["taskresult"]["data"] = base64_encode($opres);

          $pmsg = json_encode($arr_reqret);
          $abc = DoPost($apiurl,$pmsg,"","text/json","","");
          echo "return to collector\n~~~~~~~~~~~~~~~~~\n" . $abc[0] . "\n~~~~~~~~~~~~~\n";
        }
      }
    }
  }
}

function getlocalid() {
        $file = "/tmp/collector.id";
        $fdata = file_get_contents($file, FILE_USE_INCLUDE_PATH);
        $fdata = str_replace("\n","",$fdata);
        return $fdata;
}

function extractVMwareCookie($resp) {
	// get cookie
	// multi-cookie variant contributed by @Combuster in comments
	preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $resp, $matches);
	$cookies = array();
	foreach($matches[1] as $item) {
	    parse_str($item, $cookie);
	    $cookies = array_merge($cookies, $cookie);
	}

	//return $cookies;
	if(isset($cookies["vmware_soap_session"])) {
		return "vmware_soap_session=".$cookies["vmware_soap_session"];
	} else {
		return "";
	}
}
?>
