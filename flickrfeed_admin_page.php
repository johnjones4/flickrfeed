<div class="wrap">
	<div id="icon-options-general" class="icon32"><br></div>
	<h2>FlickrFeed Settings</h2>
	<form method="post" action="options.php">
		<?php settings_fields(FLICKR_SETTINGS_GROUP); ?>
		<table class="form-table">
			<tbody>
				<tr valign="top">
					<th scope="row">
						<label for="<?php echo FLICKR_SETTINGS_USERID; ?>">Flickr User ID</label>
					</th>
					<td>
						<input name="<?php echo FLICKR_SETTINGS_USERID; ?>" type="text" id="<?php echo FLICKR_SETTINGS_USERID; ?>" value="<?php echo get_option(FLICKR_SETTINGS_USERID); ?>" class="regular-text" />
						<span class="description">User <a href="http://idgettr.com/">idgettr.com</a> to get your user ID from your username.</span>
					</td>
				</tr>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button-primary" value="Save Changes" />
		</p>
	</form>
</div>