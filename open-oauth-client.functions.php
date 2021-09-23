<?php

function layout()
{
    $configurationInputs = [
        ['key' => 'app_name', 'type' => 'text', 'optionName' => 'open_oauth_app_name', 'label' => 'Application Name', 'placeholder' => 'Provide a name to your application'],
        ['key' => 'redirect_uri', 'type' => 'text', 'optionName' => 'open_oauth_redirect_uri', 'label' => 'Redirect URI', 'placeholder' => 'Set your redirect uri'],
        ['key' => 'client_id', 'type' => 'text', 'optionName' => 'open_oauth_client_id', 'label' => 'Client Id', 'placeholder' => 'Set your client id'],
        ['key' => 'client_secret', 'type' => 'text', 'optionName' => 'open_oauth_client_secret', 'label' => 'Client Secret', 'placeholder' => 'Set your client secret'],
        ['key' => 'client_scope', 'type' => 'text', 'optionName' => 'open_oauth_client_scope', 'label' => 'Client Scope', 'placeholder' => 'Set your client scope'],
        ['key' => 'authorization_endpoint', 'type' => 'text', 'optionName' => 'open_oauth_authorization_endpoint', 'label' => 'Authorization Endpoint', 'placeholder' => 'Set server authorization endpoint'],
        ['key' => 'token_endpoint', 'type' => 'text', 'optionName' => 'open_oauth_token_endpoint', 'label' => 'Token Endpoint', 'placeholder' => 'Set server token endpoint'],
        ['key' => 'userinfo_endpoint', 'type' => 'text', 'optionName' => 'open_oauth_userinfo_endpoint', 'label' => 'UserInfo Endpoint', 'placeholder' => 'Set server userinfo endpoint'],
        ['key' => 'force_auth', 'type' => 'checkbox', 'optionName' => 'open_oauth_force_auth', 'label' => 'Force Authentication'],
    ];

    $attributesInputs = [
        ['key' => 'user_name', 'type' => 'text', 'optionName' => 'open_oauth_user_name', 'label' => 'User Name', 'placeholder' => 'Enter the user name attribute received'],
        ['key' => 'user_email', 'type' => 'text', 'optionName' => 'open_oauth_user_email', 'label' => 'User Email', 'placeholder' => 'Enter the user email attribute received'],
    ];

?>

    <div class="wrap">
        <h2>Open OAuth Client</h2>
        <div>
            <div class="card">
                <h3>Configure your OAuth Client</h3>
                <form id="configuration" method="post" action="">
                    <input type="hidden" name="action" value="configuration" />
                    <?php wp_nonce_field('configuration_nonce', 'configuration_nonce') ?>
                    <table>
                        <?php foreach ($configurationInputs as $input) { ?>
                            <tr>
                                <td>
                                    <label for="<?php echo $input['key']; ?>">
                                        <?php echo $input['label']; ?>
                                    </label>
                                </td>
                                <td>
                                    <?php if ($input['type'] === 'checkbox') { ?>
                                        <input id="<?php echo $input['key']; ?>" name="<?php echo $input['key']; ?>" type="<?php echo $input['type']; ?>" <?php echo esc_attr(get_option($input['optionName'])) === 'on' ? 'checked' : ''  ?> />
                                    <?php } else { ?>
                                        <input id="<?php echo $input['key']; ?>" name="<?php echo $input['key']; ?>" type="<?php echo $input['type']; ?>" style="width: 15rem;" placeholder="<?php echo $input['placeholder']; ?>" value="<?php echo esc_attr(get_option($input['optionName'])); ?>" />
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <td colspan="2">
                                <input type="submit" value="Save" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

            <div class="card" style="margin-top: 3rem;">
                <h3>Configure your attributes mapping</h3>
                <h4>You need to fill in the attribute mapping to perform SSO. So the plugin can create the user in the wordpress using those attributes.</h4>

                <input type="button" value="Get attributes" onclick="testAttributes();" />

                <p>To see the which attributes are coming from the oauth server, please click the button and log in with a user. You can change the scope to receive more attributes relatively.</p>

                <form id="attributes" method="post" action="">
                    <input type="hidden" name="action" value="attributes" />
                    <?php wp_nonce_field('attributes_nonce', 'attributes_nonce') ?>
                    <table>
                        <?php foreach ($attributesInputs as $input) { ?>
                            <tr>
                                <td>
                                    <label for="<?php echo $input['key']; ?>">
                                        <?php echo $input['label']; ?>
                                    </label>
                                </td>
                                <td>
                                    <?php if ($input['type'] === 'checkbox') { ?>
                                        <input id="<?php echo $input['key']; ?>" name="<?php echo $input['key']; ?>" type="<?php echo $input['type']; ?>" <?php echo esc_attr(get_option($input['optionName'])) === 'on' ? 'checked' : ''  ?> />
                                    <?php } else { ?>
                                        <input id="<?php echo $input['key']; ?>" name="<?php echo $input['key']; ?>" type="<?php echo $input['type']; ?>" style="width: 15rem;" placeholder="<?php echo $input['placeholder']; ?>" value="<?php echo esc_attr(get_option($input['optionName'])); ?>" />
                                    <?php } ?>
                                </td>
                            </tr>
                        <?php } ?>
                        <tr>
                            <td colspan="2" style="padding-left: 8rem; padding-top:1rem;">
                                <input type="submit" value="Save" />
                            </td>
                        </tr>
                    </table>
                </form>
            </div>

        </div>

        <script>
            function testAttributes() {
                let testWindow = window.open('<?php echo site_url(); ?>' + '/?auth=sso&debug=true', "Test Attributes Configuration", "width=600, height=600");
            }
        </script>

    </div>
<?php

}
