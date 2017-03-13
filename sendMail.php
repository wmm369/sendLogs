<?php
class sendLog{
	
	/**
	 * 处理数据
	 * @param $rawlog
	 * @return array
	 */
	function parseLog($rawlog,$pre)
	{
		$str = '------------------------------------------------------------------------';
		$logs = explode($str, $rawlog);

		$data = array();
		foreach ($logs as $log) {
			$tmps = explode("\n", ltrim($log, "\n"));
			if (empty($tmps[0])) {
				continue;
			}
			$tmps = $this->reformatData($tmps);
			$regex = '/(r[\d]+) \| ([a-z]+) \| ([^\|]+) \| ([\d]+) (lines|line)/';
			preg_match($regex, $tmps[0], $match);

			$r['rev'] = $match[1];//更新版本号
			$r['user'] = $match[2];//操作用户
			$r['time'] = $match[3];//更新时间

			//注释的行数

			$files = preg_grep('/ [MADR]+[^\|]+(\.)*(^\|)*/', $tmps);
			$fileNum = count($files);
			$r['filenums'] = $fileNum;
			$coment = '';
			for ($i = $fileNum + 2; $i < count($tmps); $i++) {
				$coment .= $tmps[$i] . "\n";
			}
			$r['comment'] = $coment;
			$judge=strtotime($pre);
			$time=strtotime(explode('+', $r['time'])[0]);
			if($time<$judge){
				continue;
			}
			$data[] = $r;
			unset($log);
		}

		return $data;
	}

	/**
	 * 处理数据，除去数组中得空数据
	 * @param $data 数据
	 * @return array 返回整理后的数据
	 */

	function reformatData($data)
	{
		foreach ($data as $val) {
			if (!empty($val)) {
				$value[] = $val;
			} else {
				continue;
			}

		}
		return $value;

	}

	function main()
	{
		//项目列表；
		$projects = array(
			array(
				'name' => '项目名',
				'redmine' => '',//项目说明
				'svn' => '', //svn地址
			),
		);
		//获取svn 记录
		$data = array();
		$pre = date('Y-m-d', time() - 86400);//前一天
		$now = date('Y-m-d', time());//当前时间

		foreach ($projects as $project) {
			//获取对应记录
			$cmd = "svn log -vr {{$pre}}:{{$now}} {$project['svn']} ";
			var_dump($cmd);
			$logData = shell_exec($cmd);
			if ($logData) {
				$logData = trim($logData, "- \n");;
				$r = $this->parseLog($logData,$pre);
				if (!$r) {
					continue;

				}
				foreach ($r as $item) {
					$data[$item['user']][$project['name']][] = $item;
				}

			} else {
				var_dump('无更新');
				continue;
			}
		}

		$email="wmm147@qq.com";//发送邮件地址
		if ($data) {
			$data = rank($data);
			$style = "font-family:Arial;font-size:100%;border-left:1px solid #C7C7C7;border-top:1px solid #C7C7C7;border-spacing:0px;";
			$put = '';
			$put .= "<table style='" . $style . "'>\n";
			$put .= $this->genTbale(null, $pre);

			$put .= "<tbody>\n";
			$i = 1;
			foreach ($data as $key => $value) {
				$put .= $this->formatData($value['name'], $value['content'], $i);
				$i++;
			}
			$put .= "</tbody>\n";
			$put .= "</table>\n\n";
			$put .= "<a style='margin-top: 15px;font-family: Arial;font-size:95%;border-spacing:0px; display:block; color:#000;' href=#>code diary</a>";
			try {
				sendMail($email, "代码日报", $put);
			} catch (Exception $e) {
				echo $e->getMessage . "\n";
			}
		} else {
			$this->sendMail($email, "代码日报", '~~~~(>_<)~~~~ 今天大家都在偷懒，没有人写代码………………怎么破'); 
		}


	}

	//table头
	function genTbale($type = '', $date)
	{
		$style = "border-right:1px solid #C7C7C7;border-bottom:1px solid #C7C7C7; padding:0; margin:0;color:black";
		$str = "";
		$str .= "<thead>\n";
		$str .= "<tr bgcolor='#F0FFFF'>\n";
		$str .= "<th width=100% colspan='6' height='30' style='" . $style . "'>日志-$date-龙虎榜-</th>\n";
		$str .= "</tr>\n";
		$str .= "</thead>\n";
		return $str;
	}

	//格式化输出
	function formatData($key, $value = array(), $i)
	{

		$style = "border-right:1px solid #C7C7C7;border-bottom:1px solid #C7C7C7; padding:0; margin:0;padding-right:3px";
		if ($i == 1) {
			$title = '状元';
			$reason = "哎呦，我的天呐！你今天更新了".count($value)."个项目!成为今天龙虎榜的状元";
			$src="http://img0.imgtn.bdimg.com/it/u=288514605,1240644921&fm=21&gp=0.jpg";
		} elseif ($i == 2) {
			$title = '榜眼';
			$reason = "这位巨星！你也不错哦！更新了".count($value)."个项目!当榜眼也不错！再努力点，当大哥！";
			$src="http://img5.imgtn.bdimg.com/it/u=1781829819,3745364778&fm=21&gp=0.jpg";
		} elseif ($i == 3) {
			$title = '探花';
			$reason = "偶像！其实探花也是可以的！你很幸运只更新了".count($value)."个项目！争取提升一名哦！";
			$src="http://img0.imgtn.bdimg.com/it/u=3723364767,3517116745&fm=21&gp=0.jpg";
		} else {
			$title = '第' . $i . '名';
			$reason = "Well Done!哎呦不错哦！革命尚未成功，同志还需努力！";
			$src="http://img3.imgtn.bdimg.com/it/u=3900395489,2963399918&fm=21&gp=0.jpg";
		}

		$res = "";
		$res .= "<tr  bgcolor='#3CB371' >";
		$res .= "<td align='center' colspan='6' height='30' style='" . $style . "'>" . $title . ":" . $key . "</td>\n";
		$res .= "</tr>\n";

		$res .= "<tr  bgcolor='#3CB371' >";
		$res .= "<td align='center' colspan='6' height='30' style='" . $style . "'>上榜理由：" . $reason . "</td>\n";
		$res .= "</tr>\n";

		$res .= "<tr>";
		$res .= "<td align='center' colspan='6' height='30' style='" . $style . "'><img height='200' width='200'  src='".$src."'></td>\n";
		$res .= "</tr>\n";


		foreach ($value as $ke => $node) {
			$res .= "<tr  bgcolor='#FFFAFA' >";
			$res .= "<td align='left' colspan='4' width='100' height='30' style='" . $style . "'>项目：" . $ke . "</td>";
			$sum = 0;
			$res .= "</tr>\n";

			foreach ($node as $val) {
				$sum += $val['filenums'];
				$res .= "<tr>";
				$res .= "<td align='right'  width='100' height='30' style='" . $style . "'>" . $val['rev'] . "</td>\n";
				$res .= "<td align='right' width='100' height='30' style='" . $style . "'>修改文件：" . $val['filenums'] . "个</td>\n";
				$res .= "<td align='right'  width='200' height='30' style='" . $style . "'>" . explode('+', $val['time'])[0] . "</td>\n";
				$res .= "<td align='left'   width='240' height='30' style='" . $style . "'>&nbsp&nbsp&nbsp" . $val['comment'] . "</td>\n";
				$res .= "</tr>";

			}

			$res .= "<tr>";
			$res .= "<td align='center' colspan='2' width='100' height='30' style='" . $style . "'>修改次数：" . count($node) . "</td>\n";
			$res .= "<td align='center' colspan='2'  width='100' height='30' style='" . $style . "'>修改文件数：" . $sum . "个</td>\n";
			$res .= "</tr>\n";
			$res .= "</tr>\n";
		}
		return $res;
	}

	//邮件发送
	function sendMail($to, $subject, $message)
	{
		$headers = array();
		$subject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
		$headers[] = "Content-Type:text/html; charset=UTF-8";
		$from || $from = 'wmm147@qq.com';
		$headers[] = 'From: ' . $this->formatEmail($from);
		$sender = '-f' . $from;
		return mail($this->formatEmail($to), $subject, $message, implode("\n", $headers), $sender);
	}


	//邮件内容格式化
	function formatEmail($email, $encode = 'UTF-8')
	{
		$arr = explode(',', $email);
		$newArr = array();
		foreach ($arr as &$val) {
			if (preg_match("/^(.*)<([\w\-\.]+@[\w\-]+\.\w+)>$/", $val, $match)) {
				$newArr[] = $match[1] ? "=?{$encode}?B?" .
					base64_encode($match[1]) .
					'?=<' . $match[2] . '>' : $match[2];
			} else {
				$newArr[] = $val;
			}
		}
		return implode(',', $newArr);
	}

	// 用户排名，基于更新项目的数量
	function rank($data)
	{
		$i = 0;
		foreach ($data as $name => $val) {
			$tmp[$i]['name'] = $name;
			$tmp[$i]['content'] = $val;
			$tmp[$i]['rank'] = count($val);
			$i++;
		}
		usort($tmp, function ($a, $b) {
			if ($a['rank'] > $b['rank']) {
				return -1;
			}
			return 1;
		});
		return $tmp;
	}
}
