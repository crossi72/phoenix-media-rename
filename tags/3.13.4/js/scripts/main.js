// Main class to handle media rename functionality
class PhoenixMediaRename {
	constructor() {
		this.form = null;
		this.fields = null;
		this.type = null;
		this.isMediaSingle = false;
		this.fieldsCount = 0;
		this.currentField = 0;
		
		// Bind methods
		this.doRename = this.doRename.bind(this);
		this.processFormSubmit = this.processFormSubmit.bind(this);
		
		// Initialize when DOM is loaded
		document.addEventListener('DOMContentLoaded', () => this.init());
	}

	init() {
		this.form = document.getElementById('posts-filter');

		//init isMediaSingle
		this.checkMediaPage();

		// Handle media library list page
		if (!this.isMediaSingle) {
			document.querySelectorAll('.tablenav select[name^=action]').forEach(select => {
				for (let label in MRSettings.labels) {
					const option = document.createElement('option');
					option.value = label;
					option.textContent = decodeURIComponent(MRSettings.labels[label].replace(/\+/g, '%20'));
					select.insertBefore(option, select.lastElementChild);
				}
			});
		}

		// Add event listeners
		if (document.getElementById('post')) {
			document.getElementById('post').addEventListener('submit', this.processFormSubmit);
		}

		document.querySelectorAll('.tablenav .button.action').forEach(button => {
			button.addEventListener('click', this.processFormSubmit);
		});

		// Add filename textbox listeners
		this.initTextboxListeners();
	}

	/**
	 * checks if current page is single media edit page
	 */
	checkMediaPage(){
		const url = window.location.href;

		//check if page URL corresponds to media list page
		if (url.includes('/wp-admin/upload.php')) {
			this.isMediaSingle = false;
		} else {
			this.isMediaSingle = true;
		}
	}

	initTextboxListeners() {
		const textboxes = document.querySelectorAll('.phoenix-media-rename-filename');
		textboxes.forEach(textbox => {
			textbox.addEventListener('input', function() {
				const row = this.closest('tr');
				const checkbox = row.querySelector('input[type="checkbox"]');
				if (checkbox) {
					checkbox.checked = true;
				}
			});
		});
	}

	/**
	 * manage the rename (or other supported operations) process
	 * 
	 * @param {object} field field that contains the filename
	 */
	async doRename(field) {
		const formData = new FormData();
		formData.append('action', 'phoenix_media_rename');
		formData.append('type', this.type);
		formData.append('_wpnonce', this.form.querySelector('input[name=_mr_wp_nonce]').value);
		formData.append('new_filename', field.querySelector('input').value);
		formData.append('post_id', field.querySelector('input').dataset.postId);

		const loader = field.querySelector('.loader');
		const error = field.querySelector('.error');
		const success = field.querySelector('.success');

		try {
			const response = await fetch(ajaxurl, {
				method: 'POST',
				body: formData
			});
			const data = await response.text();

			loader.style.display = 'none';

			if (data !== '1') {
				error.textContent = data;
				error.style.display = 'inline-block';
			} else {
				const textInput = field.querySelector('input[type=text]');
				textInput.title = textInput.value;
				success.style.display = 'inline-block';
			}

			this.currentField++;
			if (this.currentField === this.fieldsCount) {
				this.currentField = 0;
				
				if (!this.form.querySelector('.error[style*="display: inline-block"]')) {
					this.form.submit();
				}

				//enable action buttons
				const submitButtons = document.querySelectorAll('input[type="submit"]');
				submitButtons.forEach(button => button.disabled = false);
			} else {
				this.doRename(this.fields[this.currentField]);
			}
		} catch (err) {
			console.error('Error during rename:', err);
			error.textContent = 'An error occurred during rename';
			error.style.display = 'inline-block';
			loader.style.display = 'none';
		}
	}

	/**
	 * manage the Submit action of the media form
	 * 
	 * @param {object} event
	 * @returns 
	 */
	processFormSubmit(event) {
		if (this.isMediaSingle){
			//single media page: set operation to rename
			this.type = 'rename';
		} else {
			//list media page: get selected operation
			this.type = document.querySelector('#bulk-action-selector-top').value;
		}

		if (this.type == '-1'){
			//no action selected, notify user and exit
			alert(phoenix_media_rename_strings.no_action_warning);
			return;
		}

		// Check if action is valid
		const validActions = ['rename', 'rename_retitle', 'retitle', 'retitle_from_post_title', 
							'rename_from_post_title', 'rename_retitle_from_post_title'];
		
		if (!this.isMediaSingle && !validActions.includes(this.type)) {
			//no Phoenix Media Rename action selected, exit
			return;
		}

		//enable action buttons
		const submitButtons = document.querySelectorAll('input[type="submit"]');
		submitButtons.forEach(button => button.disabled = true);

		this.form = this.isMediaSingle ? 
			document.getElementById('post') : 
			document.getElementById('posts-filter');

		if (this.isMediaSingle) {
			this.fields = Array.from(this.form.querySelectorAll('.phoenix-media-rename'))
				.filter(field => {
					const input = field.querySelector('input[type=text]');
					return input.value !== input.title;
				});
		} else {
			const checkedRows = document.querySelectorAll('#the-list input:checked');
			this.fields = Array.from(checkedRows).map(checkbox => 
				checkbox.closest('tr').querySelector('.phoenix-media-rename'));
		}

		this.fieldsCount = this.fields.length;
		if (this.fieldsCount) {
			this.fields.forEach(field => {
				const elements = field.querySelectorAll('.loader, .error, .success');
				elements.forEach(el => el.style.display = 'none');
				field.querySelector('.loader').style.display = 'inline-block';
			});

			this.doRename(this.fields[0]);
			event.preventDefault();
		}
	}
}

// Initialize the application
const phoenixMediaRename = new PhoenixMediaRename();