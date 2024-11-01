<?php
/*
Plugin Name: SmetLink WordPress plugin
Plugin URI: https://smetlink.com/
Description: Use your WordPress to forward links from your browser in to your inbox.
Version: 0.1.0
Author: Pawel Jankowski
Author URI: http://jankowski.site/
License: GPL V3
*/

/*
SmetLink WordPress plugin
Copyright (c) 2017 Pawel Jankowski (https://smetlink.com/).

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

Permission is hereby granted, free of charge, to any person obtaining
a copy of this software and associated documentation files (the
"Software"), to deal in the Software without restriction, including
without limitation the rights to use, copy, modify, merge, publish,
distribute, sublicense, and/or sell copies of the Software, and to
permit persons to whom the Software is furnished to do so, subject to
the following conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

function smetlink_register_options_page()
	{
	add_options_page('Page Title', 'SmetLink', 'manage_options', 'smetlink', 'smetlink_options_page');
	register_setting('smetlink_options_group', 'smetlink_emails', 'smetlink_callback');
	register_setting('smetlink_options_group', 'smetlink_api_key', 'smetlink_callback');
	}

add_action('admin_menu', 'smetlink_register_options_page');

function smetlink_generateRandomString($length = 10)
	{
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$charactersLength = strlen($characters);
	$randomString = '';
	for ($i = 0; $i < $length; $i++)
		{
		$randomString.= $characters[rand(0, $charactersLength - 1) ];
		}

	return $randomString;
	}

function smetlink_options_page()
	{
	$smet_api_key = get_option('smetlink_api_key');
	if ($smet_api_key == '')
		{
		$smet_api_key = smetlink_generateRandomString(30);
		}

?>
<style>
.clear{
  width:100%;
  height:0px;
  clear:boath;
}
.smet_wraper{
  padding-right:20px;
}
.smet_wraper label{
  font-weight:bold;
}
</style>
<div class="smet_wraper">
<h3>Smet<b>Link</b> Settings</h3>


<form method="post" action="options.php">
  <?php
	settings_fields('smetlink_options_group'); ?>
<hr/>
  <label for="smetlink_emails">Emails</label>
  <div class="clear" style="height:10px"></div>
  <textarea id="smetlink_emails" name="smetlink_emails" rows="10" style="width:100%;" /><?php
	echo get_option('smetlink_emails'); ?></textarea>
  <p>Please enter email addresses separated with comma that will be allowed to send links through your website.<br />
  <!--This emails can be use as email in Smet<b>Link</b> chrome extension settings.-->
		Mor info: <a href="https://smetlink.com/wordpress/" target="_blank">https://smetlink.com/wordpress/</a>
	</p>

  <div class="clear" style="height:20px"></div>

  <label for="smetlink_api_key">API Key</label>
  <div class="clear" style="height:10px"></div>
  <input type="text" id="smetlink_api_key" name="smetlink_api_key" value="<?php
	echo $smet_api_key; ?>" style="width:300px;max-width;100%;" />

  <div class="clear" style="height:20px"></div>
  <label for="smetlink_api_key">API URL</label>
  <div class="clear" style="height:0px"></div>
  <p style="font-weight:800;font-size:14px;">
	<?php echo get_site_url(); ?>/wp-json/setlink/mail/</p>

  


  <?php submit_button(); ?>
  </form>

  <div class="clear" style="height:20px"></div>


  <div class="clear" style="height:20px"></div>
  <p style="font-size:12px;">Made by <a href="http://jankowski.site" target="_blank">Pawel Jankowski</a> | Visit <a href="https://smetlink.com/" target="_blank">Smet<b>Link</b></a> website for news and updates</p>

  </div><!-- wraper -->

<?php
	}

add_filter('wp_mail_content_type', 'smetlink_set_html_content_type');

function smetlink_set_html_content_type()
	{
	return 'text/html';
	}

remove_filter('wp_mail_content_type', 'smetlink_set_html_content_type');

function smetlink_rest_process()
	{
	$allowed_emails = get_option('smetlink_emails');
	$allowed_emails = str_replace(" ", "", $allowed_emails);
	$allowed_emails_array = explode(",", $allowed_emails);
	$smet_api_key = get_option('smetlink_api_key');
	$smet_email = sanitize_email($_POST['email']);
	$smet_title = sanitize_text_field($_POST['title']);
	$smet_key = sanitize_text_field($_POST['key']);
	$smet_url = sanitize_text_field($_POST['url']);
	$smet_category = sanitize_text_field($_POST['cat']);
	if ($smet_api_key === $smet_key)
		{
		if (in_array($smet_email, $allowed_emails_array))
			{
			$to = $smet_email;
			$subject = '🎏  | ' . $smet_title;
			$headers = array(
				'Content-Type: text/html; charset=UTF-8'
			);
			$headers[] = 'From: SmetLink <smetlink@smetlink.com>';
			$body = '<p><span style="font-weight:800">Title:</span> ' . $smet_title . '</p>' . '<p><span style="font-weight:800">Link:</span> <a href="' . $smet_url . '">' . $smet_url . '</a></p>' . '<p><span style="font-weight:800">Keyword:</span> ' . $smet_category . '</p>' . '<br /><br />' . '<p style="font-size:12px;">Thank you for using Smet<span style="font-weight:800">Link</span>.</p>';
			wp_mail($to, $subject, $body, $headers);
			remove_filter('wp_mail_content_type', 'wpdocs_set_html_mail_content_type');
			$returnData = array(
				'status' => 200,
				'message' => 'success',
				'email' => $smet_email
			);
			}
		  else
			{
			$returnData = array(
				'status' => 404,
				'message' => 'error',
				'data' => 'Please register or activate your account before using SmetLink. To do that click Settings button on the bottom of this popup.',
				'email' => $smet_email
			);
			}
		}
	  else
		{
		$returnData = array(
			'status' => 404,
			'message' => 'error',
			'data' => 'Wrong API key.',
		);
		}

	//

	return $returnData;
	} //smetlink_rest_process
add_action('rest_api_init',
function ()
	{
	register_rest_route('setlink', '/mail/', array(
		'methods' => 'POST', //GET
		'callback' => 'smetlink_rest_process',
	));
	});
add_filter('plugin_action_links_' . plugin_basename(__FILE__) , 'smetlink_add_action_links');

function smetlink_add_action_links($links)
	{
	$smetlink_links = array(
		'<a href="' . admin_url('options-general.php?page=smetlink') . '">Settings</a>',
		'<a href="https://chrome.google.com/webstore/detail/smetlink/mibcoeiglamfnaechmhfopbedkdaibhe" target="_blank">Chrome extension</a>'
	);
	return array_merge($links, $smetlink_links);
	}

?>
