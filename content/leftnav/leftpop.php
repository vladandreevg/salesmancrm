<?php
/* ============================ */
/*         SalesMan CRM         */
/* ============================ */
/* (C) 2016 Vladislav Andreev   */
/*       SalesMan Project       */
/*        www.isaler.ru         */
/*        ver. 2017.x           */
/* ============================ */
?>
<div href="javascript:void(0)" title="<?=$lang['face']['More']?>" class="leftpop">

	<i class="icon-plus-circled"></i>

	<ul class="menu">

		<li><a href="javascript:void(0)" class="razdel nowrap" onclick="addTask('');"><i class="icon-calendar-1"><i class="sup icon-plus-circled"></i></i><?=$lang['face']['TodoName'][0]?></a></li>
		<?php if($userSettings['historyAddBlock'] != 'yes'){ ?>
		<li><a href="javascript:void(0)" class="razdel" onclick="addHistory('');"><i class="icon-clock"><i class="sup icon-plus-circled"></i></i><?=$lang['face']['ActName'][0]?></a></li>
		<?php } ?>
		<?php if($setEntry['enShowButtonLeft'] == 'yes' && $isEntry == 'on'){ ?>
			<li><a href="javascript:void(0)" class="razdel" onclick="editEntry('','edit')"><i class="icon-phone-squared"><i class="sup icon-plus-circled"></i></i><?=$lang[ 'face' ][ 'Request' ]?></a></li>
		<?php } ?>
		<?php if( $otherSettings[ 'expressForm'] && ( $userRights['client']['create'] || $isadmin == 'on')){?>
			<li data-id="express"><a href="javascript:void(0)" class="razdel" onclick="expressClient();"><i class="icon-building"><i class="sup icon-direction"></i></i><?=$lang['all']['Express']?></a></li>
		<?php } ?>
		<?php if($userRights['deal']['create'] || $isadmin == 'on'){?>
			<li data-id="deals"><a href="javascript:void(0)" class="razdel" onclick="editDogovor('','add');"><i class="icon-briefcase-1"><i class="sup icon-plus-circled"></i></i><?=$lang['face']['DealName'][3]?></a></li>
		<?php } ?>

	</ul>

</div>