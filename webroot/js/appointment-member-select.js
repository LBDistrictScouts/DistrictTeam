(function ($) {
    $(function () {
        const $memberSelect = $('#member-id');
        const $contactMethodSelect = $('#member-contact-method-id');
        const searchUrl = $memberSelect.data('member-search-url');
        const contactMethodsUrl = $contactMethodSelect.data('member-contact-methods-url');

        if (!searchUrl) {
            return;
        }

        $memberSelect.select2({
            ajax: {
                data: function (params) {
                    return {q: params.term || '', page: params.page || 1};
                },
                dataType: 'json',
                delay: 250,
                url: searchUrl,
            },
            minimumInputLength: 2,
            placeholder: 'Search for a member',
            theme: 'district-team',
            width: '100%',
        });

        $memberSelect.on('select2:select select2:clear', function () {
            $memberSelect.trigger('change');
        });

        let contactMethodsRequest;
        $memberSelect.on('change', function () {
            if (!contactMethodsUrl) {
                return;
            }
            if (contactMethodsRequest) {
                contactMethodsRequest.abort();
            }

            const memberId = $memberSelect.val();
            const selectedContactMethod = $contactMethodSelect.val();
            if (!memberId) {
                $contactMethodSelect.empty().append(new Option('No contact methods available', ''));
                return;
            }

            contactMethodsRequest = $.getJSON(contactMethodsUrl, {member_id: memberId})
                .done(function (payload) {
                    if ($memberSelect.val() !== memberId) {
                        return;
                    }

                    $contactMethodSelect.empty();
                    if (payload.results.length === 0) {
                        $contactMethodSelect.append(new Option('No contact methods available', ''));
                        return;
                    }
                    payload.results.forEach(function (contactMethod) {
                        $contactMethodSelect.append(new Option(contactMethod.text, contactMethod.id));
                    });
                    const hasSelectedContactMethod = $contactMethodSelect.find('option').filter(function () {
                        return this.value === selectedContactMethod;
                    }).length > 0;
                    if (hasSelectedContactMethod) {
                        $contactMethodSelect.val(selectedContactMethod);
                    }
                });
        });

        $memberSelect.trigger('change');
    });
})(jQuery);
