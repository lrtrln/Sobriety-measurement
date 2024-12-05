function loading() {
	const loader = document.getElementById('loader');
    const form = document.getElementById('Form');

    form.addEventListener('submit', (event) => {
        loader.classList.remove('hidden');
    });
}

function checkPagespeed() {
	const checkbox = document.getElementById('pagespeedCheckbox');

    checkbox.addEventListener('change', function() {
        const url = new URL(window.location);
        if (checkbox.checked) {
            url.searchParams.set('pagespeed', '1');
        } else {
            url.searchParams.delete('pagespeed');
        }
        window.history.replaceState({}, '', url);
    });
}

function addHttp() {
	const urlForm = document.getElementById('Form');
    const urlInput = document.getElementById('url');

    urlForm.addEventListener('submit', function(event) {
        let url = urlInput.value.trim();
        if (!url.match(/^https?:\/\//)) {
            url = 'https://' + url;
        }
        urlInput.value = url;
    });
}

document.addEventListener('DOMContentLoaded', function() {
	checkPagespeed();
	addHttp();
});

loading();
