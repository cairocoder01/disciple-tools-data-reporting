<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Azure
{
    public $type = 'contacts';

    public function __construct( $token, $type )
    {
        $this->token = $token;
        $this->type = $type;
        require_once( plugin_dir_path( __FILE__ ) . '../data-tools.php' );
    }

    public function content() {
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-1">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        $storage_account_key = get_option( "dt_data_reporting_azure_storage_account_key" );
	$storage_account = get_option( "dt_data_reporting_azure_storage_account" );
	$storage_account_container = get_option( "dt_data_reporting_azure_storage_account_container" );
        $settings_link = 'admin.php?page='.$this->token.'&tab=azure-setup';
        switch ($this->type) {
          case 'contacts':
          default:
            if ( empty( $storage_account_key ) ) {
		echo "<p>A Storage Account Key has not been set. Please update in <a href='$settings_link'>Azure Setup</a></p>";
	    } else if ( empty( $storage_account ) ) {
		echo "<p>A Storage Account has not been set. Please update in <a href='$settings_link'>Azure Setup</a></p>";
	    } else if ( empty( $storage_account_container ) ) {
		echo "<p>A Storage Account Container has not been set. Please update in <a href='$settings_link'>Azure Setup</a></p>";
	    } else {
		[$columns, $rows] = DT_Data_Reporting_Tools::get_contacts(true);
		$columns = array_map(function ( $column ) { return $column['name']; }, $columns);
		// TODO: do not hardcode maxmemory value
		$csv = fopen('php://temp/maxmemory:'. (100*1024*1024), 'r+');
		fputcsv($csv, $columns);
		// loop over the rows, outputting them
		foreach ($rows as $row ) {
			fputcsv( $csv, $row );
		}
		rewind($csv);
		$content = stream_get_contents($csv);
		// Azure specifics
		$blob_name = "contacts_".strval(gmdate('Ymdhi', time())).".csv";
		$connectionString = "DefaultEndpointsProtocol=https;AccountName=".$storage_account.";AccountKey=".$storage_account_key.";EndpointSuffix=core.windows.net";
		$blobClient = MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobService($connectionString);
		// TODO: RBAC Support
		//$aadtoken = "";
		//$blobClient = MicrosoftAzure\Storage\Blob\BlobRestProxy::createBlobServiceWithTokenCredential($aadtoken, $connectionString);
		try {
		  //Upload blob
		  $blobClient->createBlockBlob($storage_account_container, $blob_name, $content);
		  echo '<div class="notice notice-success notice-dt-data-reporting is-dismissible" data-notice="dt-data-reporting"><p>'.$blob_name.' successfully uploaded to Azure Blob</p></div>';
		} catch(MicrosoftAzure\Storage\Common\Exceptions\ServiceException $e){
		  $code = $e->getCode();
		  $error_message = $e->getMessage();
		  echo $code.": ".$error_message."<br />";
		}
            }
            break;
        }
    }
}
