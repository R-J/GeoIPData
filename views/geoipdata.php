<?php if (!defined('APPLICATION')) exit(); ?>

<h1><?php echo $this->Data['Title'].' '.T('Settings'); ?></h1>
<div class="Info">
   <?php echo T($this->Data['Description']); ?>
</div>
<div class="Info">
   <?php echo T('Refreshing Data may take some time, so please be patient...'); ?>
</div>
<div class="Configuration">
    <a href="<?php echo Url('plugin/geoipdata/refreshdata',TRUE); ?>" class="Button"><?php echo T('Refresh Data'); ?></a>
    <a href="<?php echo Url('plugin/geoipdata/droptables',TRUE); ?>" class="Button"><?php echo T('Drop Tables'); ?></a>
</div>
<!-- create steps for 1. creating tables and view, 2. dropping tables and view, 3. download, 4. unzipping, 5. filling table blocks, 6. filling table location, 7. delete files -->
