(function ($) {
    const {registerPlugin} = wp.plugins;
    const {PluginSidebar} = wp.editPost;
    const {Button} = wp.components;
    const {useSelect, useDispatch} = wp.data;
    const {createElement, useEffect, useState} = wp.element;

    function WPRewriteSidebar() {
        const [apiKey, setApiKey] = useState('');

        useEffect(() => {
            fetchApiKey();
        }, []);

        const fetchApiKey = () => {
            fetch('/wp-json/wp-rewrite/v1/apikey')
                .then(response => response.text())
                .then(fetchedApiKey => {
                    const modifiedApiKey = fetchedApiKey.replaceAll('"', ''); // Remove all " characters
                    setApiKey(modifiedApiKey);
                });
        };

        const selectedBlockClientId = useSelect((select) =>
            select("core/block-editor").getSelectedBlockClientId()
        );

        const [messageOfTheDay, setMessageOfTheDay] = useState('');

        useEffect(() => {
            fetchMessageOfTheDay();
        }, []);

        const fetchMessageOfTheDay = () => {
            fetch('https://api.nicheassistant.com/message_of_the_day')
                .then(response => response.json())
                .then(data => {
                    setMessageOfTheDay(data.message);
                });
        };

        const {updateBlockAttributes} = useDispatch("core/block-editor");

        const onRewriteClick = () => {
            fetchApiKey(); // Fetch the API key every time the Rewrite button is clicked

            if (!apiKey) {
                alert('API Key is not set. Please set it in the WP Rewiter settings.');
                return;
            }

            // Remove any remaining double quotation marks from the API key
            const sanitizedApiKey = apiKey.replaceAll('"', '');

            const selectedBlock = wp.data
                .select("core/block-editor")
                .getSelectedBlock();

            if (selectedBlock && selectedBlock.attributes.content) {
                const selectedText = window.getSelection().toString();

                sendToRewriteApi(selectedText, sanitizedApiKey, (suggestion) => {
                    const selection = window.getSelection();
                    if (selection.rangeCount) {
                        const range = selection.getRangeAt(0);
                        range.deleteContents();
                        range.insertNode(document.createTextNode(suggestion));

                        const isWholeParagraph = selectedText.trim() === selectedBlock.attributes.content.trim();
                        const newContent = isWholeParagraph ? suggestion : selectedBlock.attributes.content;

                        //console.log("Suggested content:", suggestion);
                        //console.log("New paragraph:", newContent);

                        updateBlockAttributes(selectedBlockClientId, {
                            content: newContent,
                        });
                    }
                });
            }
        };

        const sidebarContent = createElement(
            "div",
            {className: "wp-rewrite-sidebar-content"},
            createElement(
                "p",
                null,
                "Select a Paragraph block or Sentence, and then click the \"Rewrite\" button to rewrite its content."
            ),
            createElement(Button, {isPrimary: true, onClick: onRewriteClick}, "Rewrite"),
            createElement(
                "h2",
                null,
                "WP Rewriter MVP updates"
            ),
            createElement(
                "p",
                null,
                messageOfTheDay
            )
        );

        return createElement(
            PluginSidebar,
            {
                name: "wp-rewrite-sidebar",
                icon: "edit",
                title: "WP Rewrite",
            },
            sidebarContent
        );
    }

    registerPlugin("wp-rewrite", {
        render: WPRewriteSidebar,
    });

    function sendToRewriteApi(text, apiKey, callback) {
        var apiEndpoint =
            "https://api.openai.com/v1/engines/text-davinci-002/completions";

        $.ajax({
            method: "POST",
            url: apiEndpoint,
            headers: {
                "Content-Type": "application/json",
                Authorization: "Bearer " + apiKey,
            },
            data: JSON.stringify({
                prompt: `Rewrite the following text: "${text}"`,
                max_tokens: 100,
                n: 1,
                stop: null,
                temperature: 1,
            }),
            success: function (response) {
                //console.log("API Response:", response);
                if (response.choices && response.choices.length > 0) {
                    var suggestion = response.choices[0].text.trim();
                    //console.log("Suggested Rewrite:", suggestion);
                    callback(suggestion);
                }
            },
            error: function (error) {
                console.error("Error calling API:", error);
            },
        });
    }
})(jQuery);      
