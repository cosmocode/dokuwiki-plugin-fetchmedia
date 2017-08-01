const form = document.getElementById('fetchmedia_form');


function* flattenLinks(data) {
    yield* Object.entries(data).reduce((carry, [page, links]) => {
        const flatLinks = links.map(link => [page, link]);
        return carry.concat(flatLinks);
    }, []);
}

function decorateLiWithResult(page, link, res) {
    const selector = `li[data-id="${btoa(page + link)}"] div.li`;
    const li = document.querySelector(selector);
    const STATUS_OK = 200;

    if (res.status === STATUS_OK) {
        li.innerHTML += '<span class="result success"> OK ✔️</span>';
    } else {
        li.innerHTML += `<span class="result error"> ${res.status}: ${res.statusText} ❌</span>`;
    }
}

function requestDownloadExternalFile(linkGen) {
    const { value, done } = linkGen.next();
    if (done) {
        return;
    }

    const [page, link] = value;
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
        fetch(`${DOKU_BASE}lib/exe/ajax.php?${query}`, options)
            .then(response => response.json())
            .then((data) => {
                const links = Object.entries(data);
                if (!links.length) {
                    const noLinksMsg = window.LANG.plugins.fetchmedia['error: no links found'];
                    document.getElementById('fetchmedia_results').innerHTML = `<div id="noLinksFound"><p><em>${noLinksMsg}</em></p></div>`;
                    return;
                }
                const l10nTableHeadingPage = window.LANG.plugins.fetchmedia['table-heading: page'];
                const l10nTableHeadingLinks = window.LANG.plugins.fetchmedia['table-heading: links'];
                const tableHead = `<table class="inline"><thead><tr><th>${l10nTableHeadingPage}</th><th>${l10nTableHeadingLinks}</th></tr></thead>`;
                const tableRows = links.map(([page, mediaLinks]) => {
                    const pageUrl = `${DOKU_BASE}doku.php?id=${page}`;
                    const pageLink = `<a href="${pageUrl}" class="wikilink1">${page}</a>`;
                    return `<tr>
                        <td><span class="wikipage">${pageLink}</span></td>
                        <td><ul>${mediaLinks.map(url => `<li data-id="${btoa(page + url)}"><div class="li"><span class="mediaLink">${url}</span></div></li>`).join('')}</ul></td>
                    </tr>`;
                });
                // todo handle case that there are no external links
                const table = `${tableHead + tableRows.join('')}</table>`;
                document.getElementById('fetchmedia_results').innerHTML = table;

                const linkGen = flattenLinks(data);
                requestDownloadExternalFile(linkGen);
            });
    },
);
