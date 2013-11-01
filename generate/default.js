(function ($) {
	$('button').click(function (event) {
		var data = {
			host: $('#inputHost').val(),
			user: $('#inputUser').val(),
			password: $('#inputPassword').val(),
			database: $('#inputDatabase').val()
		};

		$.post('generate.php', data).success(function (json) {
			alert(json.message);
		});

		event.preventDefault();
	});
}(jQuery));