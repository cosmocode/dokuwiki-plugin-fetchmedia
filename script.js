jQuery(() => {
    'use strict';

    const $form = jQuery('#fetchmedia_form');
    $form.submit(
        function (event) {
            event.preventDefault();
            const options = {
                call: 'plugin_fetchmedia',
                action: 'getExternalMediaLinks',
                namespace: $form.find('input[name="namespace"]').val(),
                type: $form.find('input[name="mediatypes"]:checked').val(),
                sectok: $form.find('input[name="sectok"]').val(),
            };
            jQuery.get(DOKU_BASE + 'lib/exe/ajax.php', options).done(function displayPagesToDownload(data) {
                const tableHead = '<table class="inline"><thead><tr><th>page</th><th>Links</th></tr></thead>';
                const tableRows = Object.entries(data).map(([page, mediaLinks]) =>
                    `<tr>
                        <td><span class="wikipage">${page}</span></td>
                        <td><ul>${mediaLinks.map(url => `<li>${url}</li>`).join('')}</ul></td>
                    </tr>`
                );
                const table = tableHead + tableRows.join('') + '</table>';
                jQuery('#fetchmedia_results').html(table);
            });
        }
    );
});
