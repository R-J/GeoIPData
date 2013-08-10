<?php if (!defined('APPLICATION')) exit();

$PluginInfo['GeoIPData'] = array(
    'Name' => 'GeoIP Data',
    'Description' => 'Creates a new View "User.LastIPAddress|Country|Region|City|PostalCode|Latitude|Longitude|MetroCode|AreaCode" provided by GeoLite DB from www.maxmind.com (http://geolite.maxmind.com/download/geoip/database/GeoLiteCity_CSV/GeoLiteCity-latest.zip)',
    'Version' => '0.01',
    'Author' => 'Robin',
    'RequiredApplications' => array('Vanilla' => '>=2.0.18.8'),
    'RequiredTheme' => False, 
    'RequiredPlugins' => False,
    'RegisterPermissions' => FALSE,
  'SettingsUrl' => '/dashboard/plugin/geoipdata',
    'SettingsPermission' => 'Garden.Settings.Manage',
    'HasLocale' => FALSE,
    'License' => 'GPLv2'
);


/**
 * Gets GeoIP data from www.maxmind.com and reads it into newly created
 * tables. Provides a view so that geolocation information could easily
 * be questioned from Garden
 */
class GeoIPData extends Gdn_Plugin {

    /**
     * Called on activation of addon
     * Creates Tables, Views and download GeoIP data
     *
     * @return void
     */    
    public function Setup() {
        $this->structure();
    } // End of Setup

    /**
     * Create table GeoLiteCityBlocks: BlockID|StartIPNumber|EndIPNumber|LocationID
     * Create table GeoLiteCityLocation: 
     *  LocationID|Country|Region|City|PostalCode|Latitude|Longitude|MetroCode|AreaCode
     * Create view GeoIPData for all IPs from User.LastIPAddress
     *
     * @return void
     */    
    private function structure() {
        $Structure = Gdn::Structure();
        
        // create table for ip block ranges
        $Structure->Table('GeoLiteCityBlocks')
            ->Column('StartIPNumber', 'bigint', FALSE, 'key')
            ->Column('EndIPNumber', 'bigint', FALSE, 'key')
            ->Column('LocationID', 'int', FALSE)
            ->Set(FALSE, FALSE);

        // create table for location information
        $Structure->Table('GeoLiteCityLocation')
            ->PrimaryKey('LocationID')
            ->Column('Country', 'varchar(2)', FALSE)
            ->Column('Region', 'varchar(2)', TRUE)
            ->Column('City', 'varchar(64)', TRUE)
            ->Column('PostalCode', 'int(8)', TRUE)
            ->Column('Latitude', 'double', FALSE)
            ->Column('Longitude', 'double', FALSE)
            ->Column('MetroCode', 'varchar(3)', TRUE)
            ->Column('AreaCode', 'varchar(3)', TRUE)
            ->Set(FALSE, FALSE);
            
        // create viewfor each ip in 
        /*
        select
            u.LastIPAddress
            , l.*
        from
            User u
            join GeoLiteCityBlocks b
                on ip2long(u.LastIPAddress) >= b.StartIPNumber and ip2long(u.LastIPAddress) <= b.EndIPNumber
            join GeoLiteCityLocation l
                on b.LocationID = l.LocationID
        */
    } // End of Structure

   
   
   /**
    * Creates [Settings] button in plugin screen and links to view geoipdata
    * 
    * @param array $Sender
    *
    * @return void
    */
    public function PluginController_GeoIPData_Create($Sender) {
        $Sender->Permission('Garden.AdminUser.Only');
        $this->Dispatch($Sender, $Sender->RequestArgs);
    }
   
    /**
     * Download GeoLite DB and fills it into GeoLiteCityBlocks and GeoLiteCityLocation
     *
     * @param  array $Sender
     *
     * @return boolean $success
     */    
    public function Controller_RefreshData($Sender) {
$debugon = FALSE;        
        // define filenames
        $source = 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity_CSV/GeoLiteCity-latest.zip';
        $target = PATH_UPLOADS.DS.'GeoLiteCity-latest.zip';

        // get GeoLite DB
        set_time_limit(120);
if($debugon==FALSE)        
        file_put_contents($target, file_get_contents($source));
        
        // check for error and return on error
        if (!file_exists($target)) {
            $Sender->InformMessage(T('Error while downloading GeoLite DB to '.$target));
            $this->Controller_Index($Sender);
        }
        
        // unzip downloaded file quietly (-qq), dropping the subdirectories in
        // the zip file (-j), always overwriting existing files without
        // asking (-o) and store them in upload path (-d PATH_UPLOADS)
if($debugon==FALSE)        
        system('unzip -qqjo '.$target.' -d '.PATH_UPLOADS);

        $blocks = PATH_UPLOADS.DS.'GeoLiteCity-Blocks.csv';
        $location = PATH_UPLOADS.DS.'GeoLiteCity-Location.csv';
        
        // check if unzipping was successfull and return on error
        if(!(file_exists($blocks) && file_exists($location))) {
            $Sender->InformMessage(T('Error while unzipping GeoLite DB'));
            $this->Controller_Index($Sender);
        }
        
        // must build manual sql and so need the prefix
        $Database = Gdn::Database();
        $Px = $Database->DatabasePrefix;
        $Structure = $Database->Structure();
        
        // import GeoLiteCity-Blocks.csv
        $Sql = "LOAD DATA LOCAL INFILE '{$blocks}'
            REPLACE
            INTO TABLE `{$Px}GeoLiteCityBlocks`
            FIELDS TERMINATED BY ','
            OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            IGNORE 2 LINES
            (`StartIPNumber`, `EndIPNumber`, `LocationID`)";
 // if($debugon==FALSE)        
        set_time_limit(120);
        $Structure->Query($Sql);

        // import GeoLiteCity-Location.csv
        $Sql = "LOAD DATA LOCAL INFILE '{$location}'
            REPLACE
            INTO TABLE `{$Px}GeoLiteCityLocation`
            FIELDS TERMINATED BY ','
            OPTIONALLY ENCLOSED BY '\"'
            LINES TERMINATED BY '\n'
            IGNORE 2 LINES
            (`LocationID`, `Country`, `Region`, `City`, `PostalCode`, `Latitude`, `Longitude`, `MetroCode`, `AreaCode`)";
// if($debugon==FALSE)        
        set_time_limit(120);
        $Structure->Query($Sql);
       
        // clean up
        unlink($target);        
        unlink($blocks);
        unlink($location);

        
        $this->Controller_Index($Sender);
    } // End of Controller_RefreshData

    /**
     * Drops tables GeoLiteCityBlocks and GeoLiteCityLocation and view GeoIPData
     *
     * @param  array $Sender
     *
     * @return boolean $success
     */    
    public function Controller_DropTables($Sender) {
        $Structure = Gdn::Structure();
        $Structure->Table('GeoLiteCityBlocks')->Drop();
        $Structure->Table('GeoLiteCityLocation')->Drop();
        $Structure->Table('GeoIPData')->Drop();
        $Sender->InformMessage(T('Tables dropped'));
        $this->Controller_Index($Sender);
    } // End of Controller_DropTables
} // End of GeoIPData
?>
