
if (typeof Reports.Export === typeof undefined) {
	 Reports.Export = {};
 }
 
 Reports.Export = Garnish.Base.extend({
	 $container: null,
	 $exportBtn: null,
	 
	 init: function ($container, settings) {
		this.$container = $container;
		this.setSettings(settings, Reports.Export.defaults);
 
		this.$exportBtn = this.$container.find('#export-btn'); 
		
		this.addListener(this.$exportBtn, 'click', '_showExportHud');
	 },
	 
	 _showExportHud: function() {
		 this.$exportBtn.addClass('active');
	 
		 var $form = $('<form/>', {
			 'class': 'export-form'
		 });
	 
		 var $formatField = Craft.ui.createSelectField({
			 label: Craft.t('app', 'Format'),
			 options: [
				 {label: 'CSV', value: 'csv'}, {label: 'JSON', value: 'json'},
			 ],
			 'class': 'fullwidth',
		 }).appendTo($form);
	 
		 $('<button/>', {
			 type: 'submit',
			 'class': 'btn submit fullwidth',
			 text: Craft.t('app', 'Export')
		 }).appendTo($form)
	 
		 var $spinner = $('<div/>', {
			 'class': 'spinner hidden'
		 }).appendTo($form);
	 
		 var hud = new Garnish.HUD(this.$exportBtn, $form);
	 
		 hud.on('hide', () => {
			 this.$exportBtn.removeClass('active');
		 });
	 
		 var submitting = false;
	 
		 this.addListener($form, 'submit', function(ev) {
			 ev.preventDefault();
			 if (submitting) {
				 return;
			 }
	 
			 submitting = true;
			 $spinner.removeClass('hidden');
	 
			 var params = {
				 id: this.$exportBtn.attr('data-id'),
			 };
	 
			 params.format = $formatField.find('select').val();
	 
	 
			 if (Craft.csrfTokenValue) {
				 params[Craft.csrfTokenName] = Craft.csrfTokenValue;
			 }
	 
			 Craft.downloadFromUrl('POST', Craft.getActionUrl('reports/reports/export'), params)
				 .then(function() {
					 submitting = false;
					 $spinner.addClass('hidden');
				 })
				 .catch(function() {
					 submitting = false;
					 $spinner.addClass('hidden');
					 Craft.cp.displayError(Craft.t('app', 'A server error occurred.'));
				 });
		 });
	 },
 }, {
	defaults: {
	}
 });

