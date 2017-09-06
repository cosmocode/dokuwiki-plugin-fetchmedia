const form = document.getElementById('fetchmedia_form');


function* flattenLinks(data) {
    yield* Object.entries(data).reduce((carry, [page, links]) => {
        const flatLinks = links.map(link => [page, link]);
        return carry.concat(flatLinks);
    }, []);
}

function decorateLiWithResult(page, link, res) {
    const selector = `td[data-id="${btoa(page + link)}"]`;
    const td = document.querySelector(selector);
    const STATUS_OK = 200;

    if (res.status === STATUS_OK) {
        td.innerHTML = '<span class="result success"> OK ⬇✔️</span>';
    } else {
        td.innerHTML = `<span class="result error"> ${res.status}: ${res.status_text} ❌</span>`;
    }
}

function requestDownloadExternalFile(linkGen) {
    const { value, done } = linkGen.next();
    if (done) {
        return;
    }

    const [page, link] = value;
    const selector = `td[data-id="${btoa(page + link)}"]`;
    const td = document.querySelector(selector);
    td.innerHTML = '<span class="⏳⬇">⬇️</span>';

    const options = {
        method: 'POST',
        headers: new Headers({ 'content-type': 'application/x-www-form-urlencoded; charset=UTF-8' }),
        body: Object.entries({
            call: 'plugin_fetchmedia_downloadExternalFile',
            page,
            link,
        }).map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('&'),
        credentials: 'include',
    };

    fetch(`${DOKU_BASE}lib/exe/ajax.php`, options)
        .then(response => response.json())
        .then((res) => {
            decorateLiWithResult(page, link, res);
            requestDownloadExternalFile(linkGen);
        })
        .catch((res) => {
            console.error(res);
            requestDownloadExternalFile(linkGen);
        });
}

form.addEventListener('submit',
    (event) => {
        event.preventDefault();
        const body = {
            call: 'plugin_fetchmedia_getExternalMediaLinks',
            namespace: form.querySelector('input[name="namespace"]').value,
            type: form.querySelector('input[name="mediatypes"]:checked').value,
            sectok: form.querySelector('input[name="sectok"]').value,
        };
        const query = Object.entries(body).map(([k, v]) => `${k}=${encodeURIComponent(v)}`).join('&');
        const options = {
            method: 'GET',
            headers: new Headers({ 'content-type': 'application/x-www-form-urlencoded; charset=UTF-8' }),
            credentials: 'include',
        };
        const waitingMessage = window.LANG.plugins.fetchmedia['message: waiting for response'];
        document.getElementById('fetchmedia_results').innerHTML = `<div id="waitingMessage"><p><em>${waitingMessage}</em></p></div>`;
        fetch(`${DOKU_BASE}lib/exe/ajax.php?${query}`, options)
            .then(response => response.json())
            .then((data) => {
                const TIMEOUT_TO_SHOW_WORK = 200;
                const links = Object.entries(data);
                if (!links.length) {
                    const noLinksMsg = window.LANG.plugins.fetchmedia['error: no links found'];
                    setTimeout(() => {
                        document.getElementById('fetchmedia_results').innerHTML = `<div id="noLinksFound"><p><em>${noLinksMsg}</em></p></div>`;
                    }, TIMEOUT_TO_SHOW_WORK);
                    return;
                }
                const l10nTableHeadingPage = window.LANG.plugins.fetchmedia['table-heading: page'];
                const l10nTableHeadingLinks = window.LANG.plugins.fetchmedia['table-heading: links'];
                const l10nTableHeadingResults = window.LANG.plugins.fetchmedia['table-heading: results'];
                const tableHead = `<table class="inline"><thead><tr><th>${l10nTableHeadingPage}</th><th>${l10nTableHeadingLinks}</th><th>${l10nTableHeadingResults}</th></tr></thead>`;
                const tableRows = links.map(([page, mediaLinks]) => {
                    const pageUrl = `${DOKU_BASE}doku.php?id=${page}`;
                    const pageLink = `<a href="${pageUrl}" class="wikilink1" target="_blank">${page}</a>`;
                    const numberOfLinks = mediaLinks.length;
                    const firstUrl = mediaLinks[0];
                    const remainingLinks = mediaLinks.slice(1);
                    return `<tr>
                        <td class="wikipage" rowspan="${numberOfLinks}">${pageLink}</td>
                        <td class="mediaLink">${firstUrl}</td><td data-id="${btoa(page + firstUrl)}" class="result"></td></tr>
                        ${remainingLinks.map(url => `<tr><td class="mediaLink">${url}</td><td data-id="${btoa(page + url)}" class="result"></td></tr>`).join('')}`;
                });
                // todo handle case that there are no external links
                const table = `${tableHead + tableRows.join('')}</table>`;
                const downloadButton = `<button id="downloadNow">${window.LANG.plugins.fetchmedia['label: button download']}</button>`;

                setTimeout(() => {
                    document.getElementById('fetchmedia_results').innerHTML = downloadButton + table;
                    const linkGen = flattenLinks(data);
                    document.getElementById('downloadNow').addEventListener('click', () => requestDownloadExternalFile(linkGen));
                }, TIMEOUT_TO_SHOW_WORK);
            })
            .catch((error) => {
                console.error(error);
                const fetchError = window.LANG.plugins.fetchmedia['error: error retrieving links'];
                document.getElementById('fetchmedia_results').innerHTML = `<div id="error"><p><em>${fetchError}</em></p></div>`;
            });
    },
);
