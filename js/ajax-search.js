jQuery(document).ready(function($) {
    var searchInput = $('#ajax-search-input');
    var searchResults = $('#ajax-search-results');
    var timer;

    searchInput.on('input', function() {
        clearTimeout(timer);
        var query = $(this).val();

        if (query.length >= 3) {
            timer = setTimeout(function() {
                performSearch(query);
            }, 300);
        } else {
            searchResults.empty();
        }
    });

    function performSearch(query) {
        $.ajax({
            url: ajax_search_params.ajax_url,
            type: 'POST',
            data: {
                action: 'ajax_search',
                search_query: query
            },
            success: function(response) {
                displayResults(response);
            },
            error: function(xhr, status, error) {
                console.error('AJAX Search Error:', error);
            }
        });
    }

    function displayResults(results) {
        searchResults.empty();

        if (results.length > 0) {
            var ul = $('<ul>');
            $.each(results, function(index, item) {
                var li = $('<li>');
                var link = $('<a>', {
                    href: item.permalink,
                    text: item.title,
                    class: 'ajax-search-item ' + item.type
                });
                li.append(link);
                ul.append(li);
            });
            searchResults.append(ul);
        } else {
            searchResults.append('<p>No results found.</p>');
        }
    }
});
