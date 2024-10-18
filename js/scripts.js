document.addEventListener('DOMContentLoaded', function() {
	let form, fields, isMediaSingle, fieldsCount, currentField = 0;

	/**
	 * manage the renaming process
	 * 
	 * @param object field the field containing the new filename
	 * @param string type rename operation in progress
	 * @param string nonce WP nonce
	 */
	function doRename(field, type, nonce) {
		let xhr = new XMLHttpRequest();
		let newFilename = field.value;
		let postId = field.getAttribute('data-post-id');

		xhr.open('POST', ajaxurl, true);
		xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
		xhr.onreadystatechange = function() {
			if (xhr.readyState === 4 && xhr.status === 200) {
				let response = xhr.responseText;
				field.querySelector('.loader').style.display = 'none';

				if (response != 1) {
					field.querySelector('.error').textContent = response;
					field.querySelector('.error').style.display = 'inline-block';
				} else {
					let input = field.querySelector('input[type=text]');
					input.setAttribute('title', input.value);
					field.querySelector('.success').style.display = 'inline-block';
				}

				if (++currentField == fieldsCount) {
					currentField = 0;

					try{
						//get error notification element
						let errorElement = document.querySelector('.error');

						if (errorElement) {
							//check if error notification element is visible (error is raised)
							if (window.getComputedStyle(errorElement).display == 'none'){
								form.submit();
							};
						}
					}
					catch (err){
					}

					document.getElementById('doaction').disabled = false;
				} else {
					doRename(fields[currentField]);
				}
			}
		};

		let params = 'action=phoenix_media_rename&type=' + type +
			'&_wpnonce=' + nonce +
			'&new_filename=' + newFilename +
			'&post_id=' + postId;
		xhr.send(params);
	}

	function processFormSubmit(event) {
		let type, newFilename, nonce;

		//get WP nonce
		nonce = document.getElementById('_mr_wp_nonce').value;

		// const siblings = [...this.parentNode.children].filter(child => child.tagName === 'SELECT' && child !== this);

		// type = siblings.length ? siblings[0].value : 'rename';
		// type = this.nextElementSibling && this.nextElementSibling.tagName === 'SELECT' ? this.nextElementSibling.value : 'rename';
		// get SELECT control
		isMediaSingle = document.querySelectorAll('.wp_attachment_image').length > 0;

		if (isMediaSingle){
			//single media page: set operation to rename
			type = 'rename';
		} else {
			//list media page: get selected operation
			type = document.querySelector('#bulk-action-selector-top').value;
		}

		if (!isMediaSingle &&
			(
				type != 'rename'
				&& type != 'rename_retitle'
				&& type != 'retitle'
				&& type != 'retitle_from_post_title'
				&& type != 'rename_from_post_title'
				&& type != 'rename_retitle_from_post_title'
			)
		) {
			return;
		}

		if (isMediaSingle) {
			//single media page
			// form = document.getElementById('post');
			field = document.querySelectorAll('.phoenix-media-rename-filename');
		// ).filter(function(field) {
		// 		return field.querySelector('input[type=text]').value != field.querySelector('input[type=text]').getAttribute('title');
		// 	});

			fieldsCount = 1;
			//show loader icon and hide success and error icons
			field.parentElement.querySelector('.loader').style.display = 'inline-block';
			field.parentElement.querySelector('.error').style.display = 'none';
			field.parentElement.querySelector('.success').style.display = 'none';

			doRename(field, type, nonce);

			event.preventDefault();

		} else {
			//list media page
			//disable action button
			document.getElementById('doaction').disabled = true;
			// form = document.getElementById('posts-filter');
			// fields = Array.from(form.querySelectorAll('#the-list input:checked')).map(function(input) {
			// 	return input.closest('tr').querySelector('.phoenix-media-rename');
			// });
			fields = document.querySelectorAll('.phoenix-media-rename-filename');
			fieldsCount = fields.length;

			for (i = 0; i < fieldsCount; i++){
				//check if checkbox is checked
				if (fields[i].closest('tr').querySelector('input[type="checkbox"]').checked){
				// if (fieldsCount) {
					// fields.forEach(function(field) {
					// 	field.querySelector('.loader').style.display = 'inline-block';
					// 	field.querySelector('.error').style.display = 'none';
					// 	field.querySelector('.success').style.display = 'none';
					// });

					//show loader icon and hide success and error icons
					fields[i].parentElement.querySelector('.loader').style.display = 'inline-block';
					fields[i].parentElement.querySelector('.error').style.display = 'none';
					fields[i].parentElement.querySelector('.success').style.display = 'none';

					doRename(fields[currentField], type, nonce);

					event.preventDefault();
				}
				// }
			}
		}
	}

	// form = document.getElementById('post');

	if (!isMediaSingle) {
		document.querySelectorAll('.tablenav select[name^=action]').forEach(function(select) {
			for (var label in MRSettings.labels) {
				var option = document.createElement('option');
				option.value = label;
				option.textContent = decodeURIComponent(MRSettings.labels[label].replace(/\+/g, '%20'));
				select.insertBefore(option, select.lastElementChild);
			}
		});
	}

	try{
		document.getElementById('post').addEventListener('submit', processFormSubmit);
	}
	catch (err){
	}

	document.querySelectorAll('.tablenav .button.action').forEach(function(button) {
		button.addEventListener('click', processFormSubmit);
	});
});

document.addEventListener('DOMContentLoaded', function() {
	// Select the textbox using its class name
	const textboxes = document.querySelectorAll('.phoenix-media-rename-filename');

	textboxes.forEach(function(textbox) {
		// Add an event listener for the 'input' event
		textbox.addEventListener('input', function() {
			// Find the closest parent 'tr' element
			let row = this.closest('tr');
		
			// Within that row, find the checkbox
			let checkbox = row.querySelector('input[type="checkbox"]');
		
			// Check the checkbox
			if (checkbox) {
				checkbox.checked = true;
			}
		});
	});
});
