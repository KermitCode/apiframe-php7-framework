<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo $this->title;?></title>
<link rel="stylesheet" href="<?php echo $this->sourceUrl;?>css/index.css" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo $this->sourceUrl;?>js/jquery.min.js"></script>
<script type="text/javascript" src="<?php echo $this->sourceUrl;?>js/tendina.min.js"></script>
<link rel="stylesheet" href="<?php echo $this->sourceUrl;?>utilLib/bootstrap.min.css" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo $this->sourceUrl;?>js/common.js"></script>
</head>
<body>
    <!--顶部-->
    <div class="layout_top_header">
        <div style="float:left"><span style="font-size: 16px;line-height:45px;padding-left: 20px;color:#fff;"><?php echo $this->title;?></h1></span></div>
        <div id="ad_setting" class="ad_setting">
            <a class="ad_setting_a" href='/admin/login?logout=yes'>
                <i class="icon-user glyph-icon" style="font-size:20px;color:#fff;"></i>
                <span style="color:#fff;">退出当前平台</span>
                <!--<i class="icon-chevron-down glyph-icon"></i>-->
            </a>
        </div>
    </div>

    <!--菜单-->
    <div class="layout_left_menu">
        <ul id="menu">
            <?php 
                  $i = 0;
                  $blank = array('api', 'api', 'api');
                  foreach($this->menu as $key=>$row)
                  {
                      $open = isset($row['children'][$this->action])?'opened':'closed';
                      ?>
             <li class="childUlLi <?php echo $open;?>">                                                                              
                 <a href="<?php echo $key{0}=='#'?'#':('/'.$key);?>"  > <i class="glyph-icon <?php echo $i?'icon-location-arrow':'icon-home';?>"></i><?php echo $row['name'];?></a>
                 <?php if(!empty($row['children']))
                       {
                            echo "<ul>";
                            foreach($row['children'] as $k=>$v)
                            {
                            ?>
                     <li><a href="<?php echo '/admin/'.$k;?>" <?php if(in_array($k, $blank)) echo 'target="_blank"';?>><i class="glyph-icon icon-chevron-right"></i><?php 
                        echo $this->action==$k?"<span class='actionm'>{$v}</span>":$v; ?></li>
                 <?php
                            }
                            echo '</ul>';
                       }
                       ?>
             </li>
            <?php $i++;}?>
        </ul>
    </div>

    <!--顶部-->
    <div id="layout_right_content" class="layout_right_content">
        <div class="route_bg">
            &nbsp;&nbsp;<i class="glyph-icon icon-home"></i><a href="/admin/index">平台首页</a><i class="glyph-icon icon-chevron-right"></i>
            <?php foreach($this->menu as $key=>$row){
                    if(!isset($row['children'])) continue;
                    foreach($row['children'] as $k=>$v){
                        if($k== $this->action){ 
                            echo "<a href='#'>{$row['name']}</a>";
                            echo '<i class="glyph-icon icon-chevron-right"></i>';
                            echo "<a href='#'>{$v}</a>";
                            break 2;
                        }
                    }
                 }
                 echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a>[当前服务器IP] <span class='actionm'>{$this->serverIP}</span></a>";
                 echo "&nbsp;&nbsp;<a>[您的IP] <span class='actionm'>{$this->userIP}</span></a>";
                 
                 ?>
        </div>
        <div class="mian_content">
            <div id="page_content" style="margin-left:10px;width:98%;">
               <div class="row-fluid">
