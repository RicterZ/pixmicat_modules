<?php
class mod_showip{

	function getModuleName(){
		return 'mod_showip : 顯示部份IP/hostname';
	}

	function getModuleVersionInfo(){
		return 'v100808';
	}

	function _isgTLD($last,$add='') {
		$gtld = array('biz','com','info','name','net','org','pro','aero','asia','cat','coop','edu','gov','int','jobs','mil','mobi','museum','tel','travel','xxx');
		if(is_array($add)) {
			foreach($add as $a) {
				array_unshift($gtld,$a);
			}
		}
		foreach($gtld as $tld) {
			if($last == $tld) {
				return true;
			}
		}
		return false;
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		global $language, $PIO;
		$iphost = strtolower($post['host']);
		if(ip2long($iphost)!==false) {
			$arrLabels['{$NOW}'] .= ' (IP: '.preg_replace('/\d+\.\d+$/','*.*',$iphost).')';
		} else { // host
			$parthost=''; $iscctld = false; $isgtld = false;

			if($iphost == 'localhost') { // localhost hack
				$arrLabels['{$NOW}'] .= ' (Host: localhost)';
				return;
			}

			if(preg_match('/([\w\-]+)\.(\w+)$/',$iphost,$parts)) {

				// hinet/teksavvy/qwest/mchsi/smartone-vodafone/rr/swbell/sbcglobal/acanac/ameritech/
				// telus/charter/embarqhsd/comcast/verizon/sparqnet/taiwanmobile/userdns/pacbell/
				// comcastbusiness/fetnet/cgocable/cox/on/psu/thecloud/suddenlink/telstraclear/
				// liniacom/elisa-laajakaista/zsttk/bezeqint/arcor-ip/prtc/linearg/insightbb/george24/pipex/amis/eircom/lijbrandt/ou IP hack
				if($parts[1] == 'hinet' || $parts[1] == 'teksavvy' || $parts[1] == 'qwest'
				 || $parts[1] == 'mchsi' || $parts[1] == 'smartone-vodafone' || $parts[1] == 'rr'
				 || $parts[1] == 'swbell' || $parts[1] == 'sbcglobal' || $parts[1] == 'acanac'
				 || $parts[1] == 'ameritech' || $parts[1] == 'telus' || $parts[1] == 'charter'
				 || $parts[1] == 'embarqhsd' || $parts[1] == 'comcast' || $parts[1] == 'verizon'
				 || $parts[1] == 'sparqnet' || $parts[1] == 'taiwanmobile' || $parts[1] == 'userdns'
				 || $parts[1] == 'pacbell' || $parts[1] == 'comcastbusiness' || $parts[1] == 'fetnet'
				 || $parts[1] == 'cgocable' || $parts[1] == 'cox' || $parts[1] == 'on' || $parts[1] == 'psu'
				 || $parts[1] == 'thecloud' || $parts[1] == 'suddenlink' || $parts[1] == 'telstraclear'
				 || $parts[1] == 'liniacom' || $parts[1] == 'elisa-laajakaista' || $parts[1] == 'zsttk'
				 || $parts[1] == 'bezeqint' || $parts[1] == 'arcor-ip' || $parts[1] == 'prtc'
				 || $parts[1] == 'linearg' || $parts[1] == 'insightbb' || $parts[1] == 'george24'
				 || $parts[1] == 'pipex' || $parts[1] == 'amis' || $parts[1] == 'eircom' || $parts[1] == 'lijbrandt' || $parts[1] == 'ou') {
					if(preg_match('/^[a-z\-]*(\d+\-\d+)/',$iphost,$ipparts))
						$parthost = $ipparts[1].'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				// netvigator/bbtec/HKBN IP hack
				} elseif($parts[1] == 'netvigator' || $parts[1] == 'bbtec' || $parts[1] == 'ctinets') {
					if(preg_match('/^[a-z]*(\d{3})(\d{3})\d{3}\d{3}/',$iphost,$ipparts))
						$parthost = intval($ipparts[1]).'.'.intval($ipparts[2]).'.*.'.$parts[0];
					elseif($parts[1] == 'netvigator') {
						if (preg_match('/^(pcd\d{3})\d{3}/',$iphost,$ipparts)) // no IP hack for pcd******.netvigator.com
							$parthost = $ipparts[1].'*.'.$parts[0];
						elseif(preg_match('/^[a-z]*(\d{3})(\d{3})/',$iphost,$ipparts)) { // PCCW egg pain (n1164818021)
							if(intval($ipparts[2]) > 255) $ipparts[2] = substr($ipparts[2],0,-1);
							$parthost = $ipparts[1].'-'.$ipparts[2].'-*.'.$parts[0];
						} else
							$parthost = '*.'.$parts[0];
					} else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'pldt' || $parts[1] == 'quadranet' || $parts[1] == 'totbb' || $parts[1] == 'plus' || $parts[1] == 'ono') { // pldt/quadranet/totbb/plus/ono IP hack
					if(preg_match('/^(\d+\.\d+)/',$iphost,$ipparts))
						$parthost = $ipparts[1].'.*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'proxad') { // proxad IP hack
					if(preg_match('/^[a-z\-]*\d+\-\d+\-(\d+\-\d+)\-\d+\-\d+/',$iphost,$ipparts))
						$parthost = $ipparts[1].'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'comunitel' || $parts[1] == 'ukrtel') { // comunitel/ukrtel IP hack
					if(preg_match('/^[\w\-]*(\d+)\-(\d+)-(\d+)\-(\d+)/',$iphost,$ipparts))
						$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'gaoland') { // gaoland IP hack
					if(preg_match('/^(\d+)\.(\d+)\.(\d+)\-(\d+)/',$iphost,$ipparts))
						$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'web-pass' || $parts[1] == 'windstream' || $parts[1] == '1dial') { // web-pass/windstream/1dial IP hack
					if(preg_match('/^\w+\.\d+\.(\d+)\.(\d+)/',$iphost,$ipparts))
						$parthost = $ipparts[2].'-'.$ipparts[1].'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 't-dialin') { // t-dialin IP hack
					if(preg_match('/^[a-z]([0-9a-f]{2})([0-9a-f]{2})[0-9a-f]{2}[0-9a-f]{2}\./',$iphost,$ipparts))
						$parthost =  hexdec($ipparts[1]).'-'.hexdec($ipparts[2]).'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'tmodns') { // tmodns IP hack
					if(preg_match('/^[a-z][0-9a-f]{2}[0-9a-f]{2}([0-9a-f]{2})([0-9a-f]{2})\./',$iphost,$ipparts))
						$parthost =  hexdec($ipparts[2]).'-'.hexdec($ipparts[1]).'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'mediaways' || $parts[1] == 'optonline') { // mediaways/optonline IP hack
					if(preg_match('/^\w+\-([0-9a-f]{2})([0-9a-f]{2})[0-9a-f]{2}[0-9a-f]{2}\./',$iphost,$ipparts))
						$parthost =  hexdec($ipparts[1]).'-'.hexdec($ipparts[2]).'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'rima-tde' || $parts[1] == 'myvzw') { // rima-tde/myvzw IP hack
					if(preg_match('/^\d+\.[a-z]+\-(\d+\-\d+)\-/',$iphost,$ipparts))
						$parthost =  $ipparts[1].'-*.'.$parts[0];
					else
						$parthost = '*.'.$parts[0];
				} elseif($parts[1] == 'theplanet') { // theplanet IP hack
					if(preg_match('/^[0-9a-f]+\.[0-9a-f]+\.([0-9a-f]{2})([0-9a-f]{2})/',$iphost,$ipparts)) {
						$ipdec = hexdec($ipparts[1]);
						$parthost = hexdec($ipparts[2]).'-'.hexdec($ipparts[1]).'-*.'.$parts[0];
					} else
						$parthost = '*.'.$parts[0];
				} elseif(preg_match('/on-nets$/',$parts[1])) { // on-nets IP hack
					if(preg_match('/(\d+)\-(\d+)-on-nets/',$parts[1],$ipparts))
						$parthost = $ipparts[2].'.'.$ipparts[1].'.*.on-nets.com';
					else
						$parthost = '*-on-nets.com';
				} else {
					$lastpart = $parts[2];
					$isgtld = $this->_isgTLD($lastpart);

					if(!$isgtld) {
						$cctld = array('ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az','ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz','ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz','de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo','fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw','gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je','jm','jo','jp','ke','kg','kh','ki','km','kn','kp','kr','kw','ky','kz','la','lb','lc','li','lk','lr','ls','lt','lu','lv','ly','ma','mc','md','me','mg','mh','mk','ml','mm','mn','mo','mp','mq','mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np','nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa','re','ro','rs','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st','su','sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw','tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','za','zm','zw');
						foreach($cctld as $tld) {
							if($lastpart == $tld) {
								$iscctld = true;
								preg_match('/([\w\-]+)\.([\w\-]+)\.(\w+)$/',$iphost,$parts);
								$isgtld = $this->_isgTLD($parts[2],array('ac','ad','co','ed','go','gr'.'lg','ne','or','ind','ltd','nic','plc','vet')); // '.co.uk' etc. are common
								if($isgtld) {
									// kbronet/seed/so-net.net.tw/tfn/giga/lsc/canvas/tpgi/adam/iinet/tbcnet/xtra/nkcatv/telesp/netvision/twt1 IP hack
									if($parts[1] == 'kbronet' || $parts[1] == 'seed' || $parts[1] == 'so-net'
									 || $parts[1] == 'tfn' || $parts[1] == 'giga' || $parts[1] == 'lsc'
									 || $parts[1] == 'canvas' || $parts[1] == 'tpgi' || $parts[1] == 'adam'
									 || $parts[1] == 'iinet' || $parts[1] == 'tbcnet' || $parts[1] == 'xtra'
									 || $parts[1] == 'nkcatv' || $parts[1] == 'telesp' || $parts[1] == 'netvision' || $parts[1] == 'twt1') {
										if(preg_match('/^(\d+\-\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[0].'-*.'.$parts[0];
										elseif($parts[1] == 'seed' && preg_match('/^\w+\-(\d+\-\d+)-\d+/',$iphost,$ipparts)) // seed h* IP hack
											$parthost = $ipparts[1].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									// i-cable/singnet/optusnet/plala/rosenet/bethere/asianet/home/hidatakayama/apol/pikara/bigpond/netspace/orange.co.il/callplus/prod-infinitum IP hack
									} elseif($parts[1] == 'hkcable' || $parts[1] == 'singnet' || $parts[1] == 'optusnet'
									 || $parts[1] == 'plala' || $parts[1] == 'rosenet' || $parts[1] == 'bethere'
									 || $parts[1] == 'asianet' || $parts[1] == 'home' || $parts[1] == 'hidatakayama'
									 || $parts[1] == 'apol' || $parts[1] == 'pikara' || $parts[1] == 'bigpond'
									 || $parts[1] == 'netspace' || $parts[1] == 'orange' || $parts[1] == 'callplus' || $parts[1] == 'prod-infinitum') {
										if(preg_match('/^[a-z\-]*(\d+\-\d+)-\d+\-\d+/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'ocn' || $parts[1] == 'nttpc') { // OCN/nttpc hack (no IP hack available)
											preg_match('/([\w\-]+\.){3}(\w+)$/',$iphost,$parts);
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'infoweb') { // infoweb hack (no IP hack available)
											preg_match('/([\w\-]+\.){6}(\w+)$/',$iphost,$parts);
											$parthost = '*.'.$parts[0];
									// tcol/yournet/m1connect/exetel/megaegg/pacific IP hack
									} elseif($parts[1] == 'tcol' || $parts[1] == 'yournet' || $parts[1] == 'm1connect' || $parts[1] == 'exetel' || $parts[1] == 'megaegg' || $parts[1] == 'pacific') {
										if(preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'eaccess' || $parts[1] == 'gvt') { // eaccess/gvt IP hack
										if(preg_match('/^(\d+)\.(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[0].'.*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'tinp' || $parts[1] == 'savecom') { // tinp/savecom IP hack
										if(preg_match('/^(\d+)\-(\d+)-(\d+)\-(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'totalbb') { // totalbb IP hack
										if(preg_match('/^[\w\-]+\.(\d+)-(\d+)\-(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[3].'-'.$ipparts[2].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'tnc') { // tnc IP hack
										if(preg_match('/^[\w\-]+\.[a-z]+(\d{3})(\d{3})\d{3}/',$iphost,$ipparts))
											$parthost = intval($ipparts[1]).'-'.intval($ipparts[2]).'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'dongfong') { // dongfong IP hack
										if(preg_match('/^(\d+)\.(\d+)-(\d+)\-[a-z]+(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-'.$ipparts[2].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'mesh') { // mesh.ad.jp IP hack (partly)
										if(preg_match('/^\w+\-(\d+\-\d+)-\d+\-\d+/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'deloitte') { // deloitte IP hack
										if(preg_match('/^\w+\-(\d+\-\d+)-\d+/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'iprimus') { // iprimus IP hack
										if(preg_match('/^\d+\.\d+\-(\d+)\-(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[2].'-'.$ipparts[1].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'tm') { // tm.net.my IP hack (partly)
										if(preg_match('/^\d+\.\d+\.(\d+)\.(\d+)\.\w+\-home/',$iphost,$ipparts))
											$parthost = $ipparts[2].'-'.$ipparts[1].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == '163data' || $parts[1] == 'cta') { // 163data/cta IP hack
										if(preg_match('/^\d+\.\d+\.(\d+)\.(\d+)\./',$iphost,$ipparts))
											$parthost = $ipparts[2].'-'.$ipparts[1].'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'zaq') { // zaq IP hack
										if(preg_match('/^zaq([0-9a-f]{2})([0-9a-f]{2})[0-9a-f]{2}[0-9a-f]{2}\./',$iphost,$ipparts))
											$parthost = hexdec($ipparts[1]).'-'.hexdec($ipparts[2]).'-*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif($parts[1] == 'dion' || $parts[1] == 'kcn-tv' || $parts[1] == 'janis' || $parts[1] == 'panda-world') { // dion/kcn-tv/janis/panda-world IP hack
										if(preg_match('/^[a-z]*(\d{3})(\d{3})/',$iphost,$ipparts))
											$parthost = intval($ipparts[1]).'.'.intval($ipparts[2]).'.*.'.$parts[0];
										else
											$parthost = '*.'.$parts[0];
									} elseif(preg_match('/tinp$/',$parts[1])) { // tinp IP hack 2
										if(preg_match('/^(\d+)\-(\d+)-(\d+)\-(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[4].'-'.$ipparts[3].'-*.tinp.com.tw';
										else
											$parthost = '*.'.$parts[0];
									} else {
										$parthost = '*.'.$parts[0];
									}
								} else {
									if($parts[2] == 'wanadoo') { // wanadoo IP hack
										if(preg_match('/^[\w\-]+\.[a-z]{1}(\d+-\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									// corbina/j-cnet/numericable/telekom.at/tele2.se IP hack
									} elseif($parts[2] == 'corbina' || $parts[2] == 'j-cnet' || $parts[2] == 'numericable' || $parts[2] == 'telekom' || $parts[2] == 'tele2') {
										if(preg_match('/^(\d+\-\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[0].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									// commufa/unitymediagroup/yaroslavl/otenet/scarlet/netcabo/mtu-net/eunet/chello/net-htp/upc/t3/telfort IP hack
									} elseif($parts[2] == 'commufa' || $parts[2] == 'unitymediagroup' || $parts[2] == 'yaroslavl'
									|| $parts[2] == 'otenet' || $parts[2] == 'scarlet' || $parts[2] == 'netcabo' || $parts[2] == 'mtu-net'
									|| $parts[2] == 'eunet' || $parts[2] == 'chello' || $parts[2] == 'net-htp' || $parts[2] == 'upc' || $parts[2] == 't3' || $parts[2] == 'telfort') {
										if(preg_match('/^[a-z]*-?(\d+\-\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'bbexcite' || $parts[2] == 'estpak') { // bbexcite/estpak IP hack
										if(preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'bell') { // bell IP hack
										if(preg_match('/^\w+\-\w+\-(\d+)\./',$iphost,$ipparts)) {
											$ipparts = explode('.',long2ip($ipparts[1]));
											$parthost = $ipparts[0].'-'.$ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
										} else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'club-internet') { // club-internet IP hack
										if(preg_match('/^\w+\-\d+\-(\d+)\-(\d+)\-(\d+)\-(\d+)/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-'.$ipparts[2].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'kabel-badenwuerttemberg') { // kabel-badenwuerttemberg IP hack
										if(preg_match('/^\w+\-\w+\-(\d+\-\d+)\-\d+\-\d+/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'telecomitalia') { // telecomitalia IP hack
										if(preg_match('/^[\w\-]+\.(\d+)\-(\d+)\-/',$iphost,$ipparts))
											$parthost = $ipparts[2].'-'.$ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'belgacom') { // belgacom IP hack
										if(preg_match('/^\d+\.\d+\-(\d+)\-(\d+)\./',$iphost,$ipparts))
											$parthost = $ipparts[2].'-'.$ipparts[1].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'orange') { // orange.es IP hack
										if(preg_match('/^\d+\.[a-z]+(\d+)\-(\d+)\-/',$iphost,$ipparts))
											$parthost = $ipparts[1].'-'.$ipparts[2].'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'bredbandsbolaget') { // bredbandsbolaget IP hack
										if(preg_match('/^\w+\-[0-9a-f]{2}[0-9a-f]{2}([0-9a-f]{2})([0-9a-f]{2})\./',$iphost,$ipparts))
											$parthost = hexdec($ipparts[2]).'-'.hexdec($ipparts[1]).'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} elseif($parts[2] == 'gavle' || $parts[2] == 't-ipconnect' || $parts[2] == 'telenet' || $parts[2] == 'direct-adsl') { // gavle/t-ipconnect/telenet/direct-adsl IP hack
										if(preg_match('/^[a-z]([0-9a-f]{2})([0-9a-f]{2})[0-9a-f]{2}[0-9a-f]{2}\./',$iphost,$ipparts))
											$parthost = hexdec($ipparts[1]).'-'.hexdec($ipparts[2]).'-*.'.$parts[2].'.'.$parts[3];
										else
											$parthost = '*.'.$parts[2].'.'.$parts[3];
									} else {
										$parthost = '*.'.$parts[2].'.'.$parts[3];
									}
								}
								break;
							}
						}
					} else {
						$parthost = '*.'.$parts[0];
					}
					if(!$iscctld && !$isgtld) {
						if($parts[1] == 'ha' && $parts[2] == 'cnc') { // ha.cnc IP hack
							if(preg_match('/^(\d+)\.(\d+)\.(\d+)\.(\d+)/',$iphost,$ipparts))
								$parthost = $ipparts[4].'-'.$ipparts[3].'-*.'.$parts[1].'.'.$parts[2];
							else
								$parthost = '*.'.$parts[2].'.'.$parts[3];
						} else
							$parthost = $iphost; // unresolvable
					}
				}
			} else {
				$parthost = $iphost; // unresolvable
			}

			$arrLabels['{$NOW}'] .= ' (Host: '.$parthost.')';
		}
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}
}
?>