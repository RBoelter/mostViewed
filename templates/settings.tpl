<script>
	$(function () {ldelim}
		$('#twitterSettings').pkpHandler('$.pkp.controllers.form.AjaxFormHandler');
        {rdelim});
</script>
<form
		class="pkp_form"
		id="twitterSettings"
		method="POST"
		action="{url router=$smarty.const.ROUTE_COMPONENT op="manage" category="generic" plugin=$pluginName verb="settings" save=true}"
>
    {csrf}
    {fbvFormArea}
        {fbvFormSection title="plugins.generic.most.viewed.headline"}
            {fbvElement type="text" id="mostViewedTitle"  value=$mostViewedTitle label="plugins.generic.most.viewed.title.headline.desc"}
        {/fbvFormSection}
        {fbvFormSection title="plugins.generic.most.viewed.days"}
            {fbvElement type="text" id="mostViewedDays" class="checkNum" value=$mostViewedDays label="plugins.generic.most.viewed.days.desc"}
        {/fbvFormSection}
        {fbvFormSection title="plugins.generic.most.viewed.amount"}
            {fbvElement type="text" id="mostViewedAmount" class="checkNum" value=$mostViewedAmount label="plugins.generic.most.viewed.amount.desc"}
        {/fbvFormSection}
        {fbvFormSection title="plugins.generic.most.viewed.years"}
            {fbvElement type="text" id="mostViewedYears" class="checkNum" value=$mostViewedYears label="plugins.generic.most.viewed.years.desc"}
        {/fbvFormSection}
    {/fbvFormArea}
    {fbvFormButtons submitText="common.save"}

</form>
<script>
    document.querySelectorAll('.checkNum').forEach(function (el) {
        el.addEventListener("input", function (elem) {
            this.value = (isNaN(this.value)) ? this.value.replace(elem.data, '') : this.value;
        });
    })
</script>