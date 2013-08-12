<?php if (!defined('APPLICATION')) exit(); ?>
<link rel="stylesheet" type="text/css" href="/plugins/GeoIPData/design/geoipdata.css" />
<h1><?php echo $this->Data['Title'].' '.T('Settings'); ?></h1>
<div class="Info">
<?php echo T('This addon load GeoIP data from www.maxmind.com, unzips it and reads it into the Vanilla database so that you can use the tables GeoLiteCityBlocks and GeoLiteCityLocation.<br />
By the time of creation, the download file had more than 25 MB, so each of those steps take quite a long time and in order to avoid timeouts, I had to split these steps up. Please be patient and take your time for each step. After that you have access to this information with the view '); ?>
</div>
<div class="Configuration">
    <ol class="GeoIPDataSteps Info">
        <li>
            <div>Download data from <a href="www.maxmind.com">www.maxmind.com</a></div>
            <a href="<?php echo Url('plugin/geoipdata/download',TRUE); ?>" class="Button"><?php echo T('GO!'); ?></a>
        </li>
        <li>
            <div>Unzip GeoLiteCity-latest.zip</div>
            <a href="<?php echo Url('plugin/geoipdata/unzip',TRUE); ?>" class="Button"><?php echo T('GO!'); ?></a>
        </li>
        <li>
            <div>Read "GeoLiteCity-Blocks.csv" into database</div>
            <a href="<?php echo Url('plugin/geoipdata/blocks2db',TRUE); ?>" class="Button"><?php echo T('GO!'); ?></a>
        </li>
        <li>
            <div>Read "GeoLiteCity-Location.csv" into database</div>
            <a href="<?php echo Url('plugin/geoipdata/loc2db',TRUE); ?>" class="Button"><?php echo T('GO!'); ?></a>
        </li>
        <li>
            <div>Delete downloaded files</div>
            <a href="<?php echo Url('plugin/geoipdata/deletefiles',TRUE); ?>" class="Button"><?php echo T('GO!'); ?></a>
        </li>
    </ol>
</div>
<h1><?php echo T('Clean Up'); ?></h1>
<div class="Info">
<?php echo T('Because building the tables is a resource hungry process, the tables are not automatically deleted when you deactivate the addon. You\'ll have to delete them here.'); ?>
</div>
<div class="Configuration">
    <ul class="Info GeoIPDataSteps">
        <li>
            <div>Delete GeoIPData tables</div>
            <a href="<?php echo Url('plugin/geoipdata/droptables',TRUE); ?>" class="Button"><?php echo T('GO!'); ?></a>
        </li>
    </ul>
</div>
