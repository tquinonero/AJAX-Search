jQuery(document).ready(function($) {
    var searchInput = $('#ajax-search-input');
    var searchResults = $('#ajax-search-results');
    var timer;
    var currentFocus = -1;

    searchInput.attr('aria-autocomplete', 'list');
    searchResults.attr('role', 'listbox');

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

    searchInput.on('keydown', function(e) {
        var items = searchResults.find('li');
        if (e.keyCode == 40) { // down arrow
            currentFocus++;
            addActive(items);
        } else if (e.keyCode == 38) { // up arrow
            currentFocus--;
            addActive(items);
        } else if (e.keyCode == 13) { // enter
            e.preventDefault();
            if (currentFocus > -1) {
                if (items.length) items[currentFocus].click();
            }
        }
    });

    function addActive(items) {
        if (!items.length) return false;
        removeActive(items);
        if (currentFocus >= items.length) currentFocus = 0;
        if (currentFocus < 0) currentFocus = (items.length - 1);
        $(items[currentFocus]).addClass('active').attr('aria-selected', 'true');
        searchInput.attr('aria-activedescendant', $(items[currentFocus]).attr('id'));
    }

    function removeActive(items) {
        items.removeClass('active').attr('aria-selected', 'false');
    }

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
        searchResults.empty().attr('aria-hidden', 'false');

        if (results.length > 0) {
            var ul = $('<ul>');
            $.each(results, function(index, item) {
                var li = $('<li>', {
                    id: 'search-result-' + index,
                    role: 'option',
                    'aria-selected': 'false'
                });
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
        currentFocus = -1;
    }

    // Initialize the Speech Recognition API
    var SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    var recognition = new SpeechRecognition();

    recognition.onstart = function() {
        console.log('Voice recognition activated. Try speaking into the microphone.');
    };

    recognition.onresult = function(event) {
        var transcript = event.results[0][0].transcript;
        searchInput.val(transcript); // Set the input value to the recognized speech
        performSearch(transcript); // Perform search with the recognized speech
    };

    recognition.onerror = function(event) {
        console.error('Speech recognition error detected: ' + event.error);
    };

    // Voice search button click event
    $('#microphone-button').on('click', function() {
        recognition.start(); // Start voice recognition
    });
});
