function rbkMarkdown() {
    // console.log(rbkMarkdownJs)
    const popup = document.querySelector('.rbk-markdown--popup');
    const popup_button = document.querySelector('.rbk-markdown--button');
    const popup_button_close = document.querySelector('.rbk-markdown--button--close');
    popup_button.addEventListener('click', (e) => {
        fetch('/wp-json/markdown/' + rbkMarkdownJs.post_id)
        .then(res => res.text())
        .then((data) => {
            popup.value = ''
            // console.log(data)
            data = JSON.parse(data)
            let htmls = data.markdown.split("\n")
            // console.log(htmls)
            htmls.map((line) => {
                let curr = popup.value
                popup.value = curr + '\n' + line
            })
        })
        popup.classList.add('show')
        popup_button_close.classList.add('show')
    })
    popup_button_close.addEventListener('click', (e) => {
        popup.classList.remove('show')
        popup_button_close.classList.remove('show')
    });
}
document.addEventListener('DOMContentLoaded', () => {
    rbkMarkdown()
})
