<?php 
global $Deli_Mailchimp_Plugin;
if(!$Deli_Mailchimp_Plugin)
	return '';

$apikey = $Deli_Mailchimp_Plugin -> get_api_key();
$listid = $Deli_Mailchimp_Plugin -> get_list_id();
?>
	<h1>Mailchimp Wordpress</h1>
	<hr>
	<table>
	<h2>Credentials</h2>
		<tr>
			<td width="120">
				<strong>API key</strong>
			</td>
			<td>
				<?php 
				if(''==$apikey)
					echo "api key missing";
				else
					echo $apikey;
				?>
				<a href="/wp-admin/options-general.php">edit</a>
			</td>
		</tr>
		<tr>
			<td>
				<strong>List ID</strong>
			</td>
			<td>
				<?php 
				if(''==$apikey)
					echo "List id missing";
				else
					echo $listid;
				?>
				<a href="/wp-admin/options-general.php">edit</a>
			</td>
		</tr>

	</table>

<div>
<br>
<hr>
<h2>Add subscriber</h2>
<?php 
echo $Deli_Mailchimp_Plugin -> render_formulaire();
?>	
</div>
<br>
<hr>
<?php 
$do_api_get_list = $Deli_Mailchimp_Plugin -> do_api_get_list();
if($do_api_get_list){
	$list_datas = json_decode($do_api_get_list -> result);
	?>
	<h3><?php echo $list_datas -> name; ?></h3>
	<h4><?php echo $list_datas -> stats -> member_count; ?> active subscriber(s)</h4>
	<?php
}
//tt(json_decode($do_api_get_list -> result) -> stats );



$do_api_get_subscribers = $Deli_Mailchimp_Plugin -> do_api_get_subscribers();
if($do_api_get_subscribers){

	$members = json_decode($do_api_get_subscribers -> result );
	echo count($members -> members);
	?>
	<table>
	<?php
	foreach ($members -> members as $member) {
		?>
		<tr>
			<td>
			<?php
			echo($member -> email_address);
			?>
			</td>
			<td>
			<?php
			echo($member -> status);
			?>			
			</td>
		</tr>
		<?php
	}
	?>
	</table>
	<?php
}
 
 ?>