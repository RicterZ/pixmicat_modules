<?php
class mod_eggpoll{
	var $mypage,$conn,$rankDB,$rankNames,$rankAlphas,$rankColors,$rankMin,$shrinkThread,$addBR;

	function mod_eggpoll(){
		global $PMS;
		$PMS->hookModuleMethod('ModulePage', __CLASS__); // 向系統登記模組專屬獨立頁面
		$this->mypage = $PMS->getModulePageURL(__CLASS__);
		$this->rankDB = 'eggpoll.db'; // poll database
		$this->rankNames = array('噓爆','噓','中立','推','大推');
		$this->rankAlphas = array('30','60','100','100','100');
		$this->rankColors = array('#f00','#a00','','#274','#4b7');
		$this->rankMin = 3; // 開始評價的最少票數
		$this->shrinkThread = true; // 摺疊開版文時是否摺疊整個串 (festival用)
		$this->addBR = 2; // #postform_main - #threads 之間插入空行? (0=不插入/1=在#postform_main後/2=在#threads前)
	}

	/* Get the name of module */
	function getModuleName(){
		return 'mod_eggpoll : 文章評分機制';
	}

	/* Get the module version infomation */
	function getModuleVersionInfo(){
		return '5th.Release-dev (v100521)';
	}

	function autoHookHead(&$txt, $isReply){
		global $language;
		$txt .= '<!--script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script-->
<style type="text/css">
.eggpoll {font-size: 0.8em;}
.eggpoll .rankup, .eggpoll .rankdown { color:#fff; cursor:pointer; }
.eggpoll .rankup { background-color: #c66; }
.eggpoll .rankdown { background-color: #66c; }
.eggpoll .rtoggle { display:none; }
.eggpoll .rtoggle, .eggpoll .rtoggled { color:blue; cursor:pointer; padding:0 0.27em; font-weight:bold; border: 1px solid blue; }
</style>
<script type="text/javascript">
var postNos = new Array();
var RankTexts = ["'.implode('","',$this->rankNames).'"];
var RankAlphas = ["'.implode('","',$this->rankAlphas).'"];
var RankColors = ["'.implode('","',$this->rankColors).'"];
var shrinkThread = '.$this->shrinkThread.';
// <![CDATA[
function mod_eggpollRank(no,rank){
	$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").hide();
	$.ajax({
		url: "'.str_replace('&amp;', '&', $this->mypage).'&no="+no+"&rank="+rank,
		type: "GET",
		success: function(rv){
			if(rv.substr(0, 4)!=="+OK "){
				$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").css("display","inline");
				alert(rv);
				return false;
			}
			rv = $.parseJSON(rv.substr(4));
			updateAppearance(rv.polls[0].no,rv.polls[0].rank,1);
		},
		error: function(){
			$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").css("display","inline");
			alert("Network error.");
		}
	});
}
function mod_eggpollToggle(o,no){
	if(o.className=="rtoggle") {
		o.className="rtoggled";
		$("div#r"+no+">.quote, div#g"+no+">.quote, div#r"+no+" a>img").slideToggle();
		if(shrinkThread) $("div#g"+no).height("auto");
	} else {
		o.className="rtoggle";
		$("div#r"+no+">.quote, div#g"+no+">.quote, div#r"+no+" a>img").slideUp();
		if(shrinkThread) $("div#g"+no).height("1.3em");
	}
}
function updateAppearance(no,rank,voted) {
	if(RankAlphas[rank] != "null") {
		$("div#r"+no).fadeTo("fast",parseInt(RankAlphas[rank])/100);
	}
	$("span#ep"+no+">.ranktext").html(RankTexts[rank]);
	$("span#ep"+no+">.ranktext").css("color",RankColors[rank]);
	if(rank==0) {
		$("span#ep"+no+">.rtoggle").show();
		$("div#r"+no+">.quote, div#g"+no+">.quote, div#r"+no+" a>img").slideUp();
		if(shrinkThread) $("div#g"+no).height("1.3em");
	}
	if(voted) {
		$("span#ep"+no+">.rankup, span#ep"+no+">.rankdown").hide();
	}
}
function getPollValues() {
	'.($this->addBR==1?'$("#postform_main").after("<br/>");':($this->addBR==2?'$("#threads").before("<br/>");':'')).'
	$.getJSON("'.str_replace('&amp;', '&', $this->mypage).'&get="+postNos,function(data) {
		$.each(data.polls, function(i,poll){
			updateAppearance(poll.no,poll.rank,poll.voted);
		});
	});
}
// ]]>
</script>';
	}

	function autoHookFoot(&$foot){
		global $language;
		$foot .= '<script type="text/javascript">if(postNos.length)getPollValues();</script>';
	}

	function autoHookThreadPost(&$arrLabels, $post, $isReply){
		global $language, $PIO;
		$arrLabels['{$QUOTEBTN}'] .= '&nbsp;<script type="text/javascript">postNos.push('.$post['no'].');</script><span class="eggpoll" id="ep'.$post['no'].'"><span class="rankup" onclick="mod_eggpollRank('.$post['no'].',1)">＋</span><span class="rankdown" onclick="mod_eggpollRank('.$post['no'].',0)">－</span><span class="ranktext">'.$this->rankNames[2].'</span> <a class="rtoggle" onclick="mod_eggpollToggle(this,'.$post['no'].')">↕</a></span>';
	}

	function autoHookThreadReply(&$arrLabels, $post, $isReply){
		$this->autoHookThreadPost($arrLabels, $post, $isReply);
	}

	function autoHookRegistBegin(&$name, &$email, &$sub, &$com, $upfileInfo, $accessInfo, $isReply){
		if(adminAuthenticate('check')) return; // 登入權限允許標籤留存不轉換 (後端登入修改文章後推文仍有效)
		if(strpos($com, $this->PUSHPOST_SEPARATOR."\r\n") !== false){ // 防止不正常的插入標籤形式
			$com = str_replace($this->PUSHPOST_SEPARATOR."\r\n", "\r\n", $com);
		}
	}

	function _calcRank($up,$down) {
		$avg=($up+$down)/2;
		if($up + $down < $this->rankMin) return 2; // too few votes
		if($up > $down) {
			if($avg * 1.05 > $up) return 2; // up < avg*1.05
			else {
				if($avg * 1.1 > $up) return 3;// avg*1.05 < up < avg*1.1
				else if($up - $avg * 1.1 >1) return 4;
				else return 3;
			}
		} else if($up < $down) {
			if($avg * 1.05 > $down) return 2; // down < avg*1.05
			else {
				if($avg * 1.1 > $down) return 1;// avg*1.05 < down < avg*1.1
				else if($down - $avg * 1.1 >1) return 0;
				else return 1;
			}
		} else return 2;
	}

	function _getPollValues($no) {
		$ip = getREMOTE_ADDR(); $datestr = gmdate('Ymd',time()+TIME_ZONE*60*60); $voted = array(); $first = true;
		$qry = 'SELECT no,ip,date FROM eggpoll_detail WHERE ip = "'.$ip.'" AND date = "'.$datestr.'" AND no IN('.$no.')';
		$rs = sqlite_query($this->conn,$qry);
		while($row = sqlite_fetch_array($rs)) { $voted[$row['no']]=1; }

		$qry = 'SELECT * FROM eggpoll_votes WHERE no IN('.$no.')';
		$rs = sqlite_query($this->conn,$qry);
		echo '{
			"polls" : [';
		while($row = sqlite_fetch_array($rs)) {
			if(!$first) echo ',';
			echo '{ "no" : '.$row['no'].',
				"rank" : '.$this->_calcRank($row['up'],$row['down']).',
				"voted" : '.(isset($voted[$row['no']]) ? 1 : 0).' }';
			if($first) $first=false;
		}
		echo ']
}';
	}

	function ModulePage(){
		global $PIO, $PTE, $language; $sqlerr = ''; $nodb = false;
		if(!file_exists($this->rankDB)) $nodb = true;
		$this->conn = sqlite_popen($this->rankDB,0666,$sqlerr);
		if($nodb) {
			$str = "CREATE TABLE [eggpoll_votes] (
[no] INTEGER  PRIMARY KEY NOT NULL,
[up] INTEGER DEFAULT '0' NOT NULL,
[down] INTEGER DEFAULT '0' NOT NULL
);
CREATE TABLE [eggpoll_detail] (
[no] INTEGER NOT NULL,
[option] INTEGER DEFAULT '0' NOT NULL,
[ip] TEXT NOT NULL,
[date] TEXT  NOT NULL
);
CREATE INDEX eggpoll_detail_index_ip_date ON eggpoll_detail(ip,date);";
			sqlite_exec($this->conn,$str,$sqlerr);
			if($sqlerr) echo $sqlerr;
		}

		if(isset($_GET['get'])) {
			$this->_getPollValues($_GET['get']);
		}
		else if(isset($_GET['no'])&&isset($_GET['rank'])){
//			if($_SERVER['REQUEST_METHOD'] != 'POST') die(_T('regist_notpost')); // 傳送方法不正確
			$ip = getREMOTE_ADDR(); $datestr = gmdate('Ymd',time()+TIME_ZONE*60*60);
			$no = intval($_GET['no']); $rank = intval($_GET['rank']);

			// 查IP
			$baninfo = '';
			$host = gethostbyaddr($ip);
			if(BanIPHostDNSBLCheck($ip, $host, $baninfo)) die(_T('regist_ipfiltered', $baninfo));

			$post = $PIO->fetchPosts($no);
			if(!count($post)) die('[Error] Post does not exist.'); // 被推之文章不存在

			$qry = 'SELECT no,ip,date FROM eggpoll_detail WHERE ip = "'.$ip.'" AND date = "'.$datestr.'" AND no ="'.$no.'"';
			$rs = sqlite_query($this->conn,$qry);
			if(sqlite_num_rows($rs)) die('[Error] Already voted.');

			$str = 'INSERT INTO eggpoll_detail (no,option,ip,date) VALUES ('.$no.','.$rank.',"'.$ip.'","'.$datestr.'")';
			sqlite_exec($this->conn,$str,$sqlerr);
			if($sqlerr) echo $sqlerr;

			$qry = 'SELECT * FROM eggpoll_votes WHERE no ='.$no;
			$rs = sqlite_query($this->conn,$qry);
			if(!sqlite_num_rows($rs)) {
				$str = 'INSERT INTO eggpoll_votes (no,up,down) VALUES ('.$no.($rank?',1,0)':',0,1)');
			} else {
				if($rank)
					$str = 'UPDATE eggpoll_votes SET up = up+1 WHERE no='.$no;
				else
					$str = 'UPDATE eggpoll_votes SET down = down+1 WHERE no='.$no;
			}
			sqlite_exec($this->conn,$str,$sqlerr);
			if($sqlerr) echo $sqlerr;

			echo '+OK ';
			$this->_getPollValues($no);
		}
	}

}
?>