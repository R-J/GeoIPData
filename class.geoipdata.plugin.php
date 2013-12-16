<?php if (!defined('APPLICATION')) exit();

$PluginInfo['GeoIPData'] = array(
    'Name' => 'GeoIP Data',
    'Description' => 'Stores GeoIP information provided from www.maxmind.com into database for fast access to longitude, latitude, country and city.',
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
     * Creates Tables
     *
     * @return void
     */    
    public function Setup() {
        $this->structure();
    } // End of Setup

    /**
     * Creates table GeoLiteCityBlocks: BlockID|StartIPNumber|EndIPNumber|LocationID
     * Creates table GeoLiteCityLocation: 
     *  LocationID|Country|Region|City|PostalCode|Latitude|Longitude|MetroCode|AreaCode
     * Create view vw_GeoIPData for all IPs from User.LastIPAddress
     *
     * @return void
     */    
    private function structure() {
        $Structure = Gdn::Structure();
        
        // create table for ip block ranges
        if (!$Structure->Table('GeoLiteCityBlocks')->TableExists()) {
            $Structure->Table('GeoLiteCityBlocks')
                ->Column('StartIPNumber', 'bigint', FALSE, 'key')
                ->Column('EndIPNumber', 'bigint', FALSE, 'key')
                ->Column('LocationID', 'int', FALSE)
                ->Set(FALSE, FALSE);
        }
        
        // create table for location information
        if (!$Structure->Table('GeoLiteCityLocation')->TableExists()) {
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
        }

        // create view for User.LastIPAddress
        $Sql = Gdn::SQL()->Select('u.LastIPAddress')
            ->Select('l.*')
            ->From('User u')
            ->Join('GeoLiteCityBlocks b', ' INET_ATON(u.LastIPAddress) >= b.StartIPNumber and  INET_ATON(u.LastIPAddress) <= b.EndIPNumber')
            ->Join('GeoLiteCityLocation l', 'b.LocationID = l.LocationID')
            ->GetSelect();
        $Structure->View('vw_GeoIPData', $Sql);

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
     * Download GeoLite DB from maxmind server
     *
     * @param  array $Sender
     *
     * @return void
     */    
    public function Controller_Download($Sender) {
        // define filenames
        $source = 'http://geolite.maxmind.com/download/geoip/database/GeoLiteCity_CSV/GeoLiteCity-latest.zip';
        $target = PATH_UPLOADS.DS.'GeoLiteCity-latest.zip';

        // to avoid timeouts, raise time limit
        set_time_limit(120);
        // get GeoLite DB
        file_put_contents($target, file_get_contents($source));

        if (!file_exists($target)) {
            $Sender->InformMessage(T('Error while downloading GeoLite DB to '.$target));
        } else {
            $Sender->InformMessage(T('Downloading GeoLiteCity-latest.zip'));
        }
        $this->Controller_Index($Sender);
    } // End of Controller_Download
    
    /**
     * Unzip deflates GeoLiteCity-latest.zip to 
     * GeoLiteCity-Blocks.csv and GeoLiteCity-Location.csv
     *
     * @param  array $Sender
     *
     * @return void
     */    
    public function Controller_Unzip($Sender) {
        $zipfile = PATH_UPLOADS.DS.'GeoLiteCity-latest.zip';
        // check for file exists and is bigger than 21 MB
        if (file_exists($zipfile) && filesize($zipfile) >  21000000) {
            // unzip downloaded file quietly (-qq), dropping the subdirectories in
            // the zip file (-j), always overwriting existing files without
            // asking (-o) and store them in upload path (-d PATH_UPLOADS)
            system('unzip -qqjo '.$zipfile.' -d '.PATH_UPLOADS);
            $Sender->InformMessage(T('File GeoLiteCity-latest.zip has been unzipped.'));
        } else {
            $Sender->InformMessage(T('File GeoLiteCity-latest.zip does not exist or is corrupt!'));
        }
        $this->Controller_Index($Sender);
    } // End of Unzip
    
    /**
     * Blocks2DB inserts file GeoLiteCity-Blocks.csv
     * into table GeoLiteCityBlocks
     *
     * @param  array $Sender
     *
     * @return void
     */    
    public function Controller_Blocks2DB($Sender) {
        // import GeoLiteCity-Blocks.csv
        $infile = PATH_UPLOADS.DS.'GeoLiteCity-Blocks.csv';
        // check if file exists and has reasonable size
        if (file_exists($infile) && filesize($infile) > 60000000) {
            // ensure tables exist
            $this->structure();
            // must build manual sql and so need the prefix
            $Px = Gdn::Database()->DatabasePrefix;

            $Sql = "LOAD DATA LOCAL INFILE '{$infile}'
                REPLACE
                INTO TABLE `{$Px}GeoLiteCityBlocks`
                FIELDS TERMINATED BY ','
                OPTIONALLY ENCLOSED BY '\"'
                LINES TERMINATED BY '\n'
                IGNORE 2 LINES
                (`StartIPNumber`, `EndIPNumber`, `LocationID`)";

            // file is huge so we need to raise time limit in order to avoid timeouts
            set_time_limit(120);
            
            Gdn::Structure()->Query($Sql);

            $Sender->InformMessage(T('File GeoLiteCity-Blocks.csv has been uploaded to database.'));
        } else {
            $Sender->InformMessage(T('File GeoLiteCity-Blocks.csv does not exist or is corrupt!'));
        }
        $this->Controller_Index($Sender);
    } // End of Controller_Blocks2DB
    
    /**
     * Loc2DB inserts file GeoLiteCity-Location.csv
     * into table GeoLiteCityLocation
     *
     * @param  array $Sender
     *
     * @return void
     */    
    public function Controller_Loc2DB($Sender) {
        // import GeoLiteCity-Location.csv
        $infile = PATH_UPLOADS.DS.'GeoLiteCity-Location.csv';
        // check if file exists and reasonable big
        if (file_exists($infile) && filesize($infile) > 22000000) {
            // ensure tables exist
            $this->structure();
            // must build manual sql and so need the prefix
            $Px = Gdn::Database()->DatabasePrefix;

            $Sql = "LOAD DATA LOCAL INFILE '{$infile}'
                REPLACE
                INTO TABLE `{$Px}GeoLiteCityLocation`
                FIELDS TERMINATED BY ','
                OPTIONALLY ENCLOSED BY '\"'
                LINES TERMINATED BY '\n'
                IGNORE 2 LINES
                (`LocationID`, `Country`, `Region`, `City`, `PostalCode`, `Latitude`, `Longitude`, `MetroCode`, `AreaCode`)";

            // file is huge so we need to raise time limit in order to avoid timeouts
            set_time_limit(120);
            
            Gdn::Structure()->Query($Sql);

            $Sender->InformMessage(T('File GeoLiteCity-Location.csv has been uploaded to database.'));
        } else {
            $Sender->InformMessage(T('File GeoLiteCity-Location.csv does not exist or is corrupt!'));
        }
        $this->Controller_Index($Sender);
    } // End of Controller_Loc2DB
        
    /**
     * DeleteFiles deletes downloaded files
     *
     * @param  array $Sender
     *
     * @return void
     */    
    public function Controller_DeleteFiles($Sender) {
        unlink(PATH_UPLOADS.DS.'GeoLiteCity-Blocks.csv');
        unlink(PATH_UPLOADS.DS.'GeoLiteCity-Location.csv');
        unlink(PATH_UPLOADS.DS.'GeoLiteCity-latest.zip');
        
        $Sender->InformMessage(T('Files deleted'));
        $this->Controller_Index($Sender);
    } // End of Controller_DeleteFiles
        
    /**
     * DropTables dops tables GeoLiteCityBlocks
     * and GeoLiteCityLocation and view GeoIPData
     *
     * @param  array $Sender
     *
     * @return void
     */    
    public function Controller_DropTables($Sender) {
        $Database = Gdn::Database();
        $Structure = Gdn::Structure();
        $Structure->Table('GeoLiteCityBlocks')->Drop();
        $Structure->Table('GeoLiteCityLocation')->Drop();
  
        $Px = Gdn::Database()->DatabasePrefix;
        $Structure->Query("DROP VIEW IF EXISTS {$Px}vw_GeoIPData");
        
        $Sender->InformMessage(T('Tables dropped'));
        $this->Controller_Index($Sender);
    } // End of Controller_DropTables
    
} // End of GeoIPData
