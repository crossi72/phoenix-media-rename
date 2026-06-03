// Immediately Invoked Function Expression (IIFE)
(() => {
	// Add event handler to "sanitize filenames" checkbox when DOM is loaded
	document.addEventListener('DOMContentLoaded', () => {
		document.getElementById('pmr_sanitize_filenames')
			.addEventListener('click', checkAccents);
	});

	// Add event handler to "remove accents" checkbox when DOM is loaded
	document.addEventListener('DOMContentLoaded', () => {
		document.getElementById('pmr_remove_accents')
			.addEventListener('click', checkSanitize);
	});

	// Change "remove accents" checkbox state if needed
	const checkAccents = () => {
		if (document.getElementById('pmr_sanitize_filenames').checked) {
			// "sanitize filenames" is on: remove accents has to be on
			document.getElementById('pmr_remove_accents').checked = true;
		}
	};

	// Change "remove accents" checkbox state if needed
	const checkSanitize = () => {
		if (!document.getElementById('pmr_remove_accents').checked && 
			document.getElementById('pmr_sanitize_filenames').checked) {
			// "sanitize filenames" is on: remove accents has to be on
			document.getElementById('pmr_remove_accents').checked = true;
		}
	};
})();