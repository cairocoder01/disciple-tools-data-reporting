<?php

if ( ! defined( 'ABSPATH' ) ) { exit; // Exit if accessed directly
}

class DT_Data_Reporting_Tab_Azure_Setup
{
    public $token;
    public function __construct( $token ) {
        $this->token = $token;
    }

    public function content() {
        $this->save_settings();
        ?>
        <div class="wrap">
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <!-- Main Column -->

                        <?php $this->main_column() ?>

                        <!-- End Main Column -->
                    </div><!-- end post-body-content -->
                    <div id="postbox-container-1" class="postbox-container">
                    </div><!-- postbox-container 1 -->
                    <div id="postbox-container-2" class="postbox-container">
                    </div><!-- postbox-container 2 -->
                </div><!-- post-body meta box container -->
            </div><!--poststuff end -->
        </div><!-- wrap end -->
        <?php
    }

    public function main_column() {
        $preview_link = 'admin.php?page='.$this->token.'&tab=preview&type=';
        $api_action_link = 'admin.php?page='.$this->token.'&tab=api-send&type=';
        $storage_account = get_option( "dt_data_reporting_azure_storage_account" );
        $storage_account_container = get_option( "dt_data_reporting_azure_storage_account_container" );
        $storage_account_key = get_option( "dt_data_reporting_azure_storage_account_key" );
        ?>
	<script>
		// see: https://www.30secondsofcode.org/blog/s/copy-text-to-clipboard-with-javascript
		function copyText(elementId) {
		  const el = document.createElement('textarea');
		  el.value = document.getElementById(elementId).value;
		  el.setAttribute('readonly', '');
		  el.style.position = 'absolute';
		  el.style.left = '-9999px';
		  document.body.appendChild(el);
		  el.select();
		  document.execCommand('copy');
		  document.body.removeChild(el);
		}
	</script>
      <form method="POST" action="">
        <?php wp_nonce_field( 'security_headers', 'security_headers_nonce' ); ?>
        <table class="widefat striped">
            <thead>
            <th><h2>Run the following commands in <a href="https://docs.microsoft.com/en-us/azure/cloud-shell/overview">Azure Cloud Shell</a></h2></th>
            </thead>
            <tbody>
            <tr>
                <td>
		    <h2>1. Create Resource Group (optional)</h2>
		    <div style="margin-left: 20px;">
			<i>NOTE: (optional, if you choose to reuse an existing Resource Group)</i>
		    </div>
		    <div style="margin: 20px;">
			<button onclick="copyText('AzResourceGroup');" type="button" class="button" style="float: right;">Copy 📋</button>
			<input 	type="text" 
				style="background:#eee; border:0 none; width: 90%; display: block; font-family: monospace; white-space: pre; margin: 1em 0;"
				id="AzResourceGroup"
				value='az group create --name discipletools --location "US East"'
			/> 
                    </div>
                </td>
            </tr>
            <tr>
                <td>
		    <h2>2. Create Storage Account</h2>
		    <div style="margin: 20px;">
			<button onclick="copyText('AzStorageAccount')" type="button" class="button" style="float: right;">Copy 📋</button>
			<input 	type="text" 
				style="background:#eee; border:0 none; width: 90%; display: block; font-family: monospace; white-space: pre; margin: 1em 0;"
				id="AzStorageAccount"
				value='az storage account create --resource-group discipletools --name discipletools --location "US East" --sku Standard_ZRS --encryption-services blob'
			/> 
                    </div>
		    <div style="margin: 20px;">
			<span>
				<input type="text"
				   name="storage-account"
				   value="<?php echo isset($storage_account) ? $storage_account : "discipletools" ?>"
				   placeholder="discipletools"
				   style="width: 60%;" />
				<button type="submit" class="button">Save 💾</button>
			</span>
		    </div>
                </td>
            </tr>
            <tr>
                <td>
		    <h2>3. Create Blob Container</h2>
		    <div style="margin: 20px;">
			<button onclick="copyText('AzStorageContainer')" type="button" class="button" style="float: right;">Copy 📋</button>
			<input 	type="text" 
				style="background:#eee; border:0 none; width: 90%; display: block; font-family: monospace; white-space: pre; margin: 1em 0;"
				id="AzStorageContainer"
				value='az storage container create --name discipletools --account-name discipletools'
			/> 
                    </div>
		    <div style="margin: 20px;">
			<span>
				<input type="text"
				   name="storage-account-container"
				   value="<?php echo isset($storage_account_container) ? $storage_account_container : "discipletools" ?>"
				   placeholder="discipletools"
				   style="width: 60%;" />
				<button type="submit" class="button">Save 💾</button>
			</span>
		    </div>
                </td>
            </tr>
            <tr>
                <td>
		    <h2>4A. Provide Storage Account Key</h2>
		    <div style="margin-left: 20px;">
			<i>(<a href="https://docs.microsoft.com/en-us/azure/storage/common/storage-account-keys-manage?tabs=azure-portal#view-account-access-keys">https://docs.microsoft.com/en-us/azure/storage/common/storage-account-keys-manage?tabs=azure-portal#view-account-access-keys</a>)</i>
		    </div>
		    <div style="margin: 20px;">
			<span>
				<input type="password"
				   name="storage-account-key"
				   value="<?php echo isset($storage_account_key) ? $storage_account_key : "" ?>"
				   style="width: 60%;" />
				<button type="submit" class="button">Save 💾</button>
			</span>
		    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <h2>4B. Create RBAC Role (coming soon, to replace need for providing storage account key)</h2>
		    <div style="margin-left: 20px;">
			<i>NOTE: (replace &lt;subscription&gt; with your Subscription)</i>
		    </div>
		    <div style="margin: 20px;">
			<button onclick="copyText('AzRBAC')" type="button" class="button" style="float: right;">Copy 📋</button>
			<input 	type="text" 
				style="background:#eee; border:0 none; width: 90%; display: block; font-family: monospace; white-space: pre; margin: 1em 0;"
				id="AzRBAC"
				value='az ad sp create-for-rbac --name DISCIPLETOOLS --role "Storage Blob Data Contributor" --scopes /subscriptions/&lt;subscription&gt;/resourceGroups/discipletools/providers/Microsoft.Storage/storageAccounts/discipletools'
			/> 
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <h2>References</h2>
                    <div>
			<ul>
			<li><a href="https://docs.microsoft.com/en-us/azure/storage/blobs/storage-quickstart-blobs-cli">https://docs.microsoft.com/en-us/azure/storage/blobs/storage-quickstart-blobs-cli</a></li>
			<li><a href="https://docs.microsoft.com/en-us/azure/role-based-access-control/built-in-roles">https://docs.microsoft.com/en-us/azure/role-based-access-control/built-in-roles</a></li>
                    </div>
                </td>
            </tr>
            </tbody>
        </table>
	</form>
        <?php
    }

    public function save_settings() {
      if ( !empty( $_POST ) ){
        if ( isset( $_POST['security_headers_nonce'] ) && wp_verify_nonce( sanitize_key( $_POST['security_headers_nonce'] ), 'security_headers' ) ) {
          if ( isset( $_POST['storage-account'] ) ) {
            update_option("dt_data_reporting_azure_storage_account", $_POST['storage-account'] );
          }
          if ( isset( $_POST['storage-account-container'] ) ) {
            update_option("dt_data_reporting_azure_storage_account_container", $_POST['storage-account-container'] );
          }
          if ( isset( $_POST['storage-account-key'] ) ) {
            update_option("dt_data_reporting_azure_storage_account_key", $_POST['storage-account-key'] );
          }
          echo '<div class="notice notice-success notice-dt-data-reporting is-dismissible" data-notice="dt-data-reporting"><p>Settings Saved</p></div>';
        }
      }
    }
}
