$(function () {
	const progressBar = $("#progressBar");
	let width = 0;
	setInterval(() => {
		width++;
		if (width > 100) {
			width = 0;
		}
		progressBar.width(width + '%');
	}, 199);
})