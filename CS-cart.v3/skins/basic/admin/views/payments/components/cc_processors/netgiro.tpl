{* alt-team.com $ hex *}

<div class="form-field">
	<label for="merchant_id">ApplicationID:</label>
	<input type="text" name="payment_data[processor_params][netgiro_application_id]" id="netgiro_application_id" value="{$processor_params.netgiro_application_id}" class="input-text" size="60" />
</div>

<div class="form-field">
	<label for="merchant_id">SecretKey:</label>
	<input type="text" name="payment_data[processor_params][netgiro_secret_key]" id="netgiro_secret_key"
	       value="{$processor_params.netgiro_secret_key}" class="input-text" size="60"/>
</div>


<div class="form-field">
	<label for="mode">{$lang.test_live_mode}:</label>
	<select name="payment_data[processor_params][mode]" id="mode">
		<option value="test"{if $processor_params.mode eq "test"} selected="selected"{/if}>{$lang.test}</option>
		<option value="live"{if $processor_params.mode eq "live"} selected="selected"{/if}>{$lang.live}</option>
	</select>
</div>
