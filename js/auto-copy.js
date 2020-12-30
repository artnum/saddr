window.addEventListener('load', () => {
    let elements = document.querySelectorAll('*[data-autocopy]')
    elements.forEach((e) => {
        if (e.textContent.trim() === '') { return; }
        window.requestAnimationFrame(() => {
            e.classList.add('toClipboardOnClick')
        })
        e.addEventListener('click', event => {
            navigator.clipboard.writeText(event.target.textContent).then(() => {
                window.requestAnimationFrame(() => {
                    event.target.classList.add('toClipboardDone')
                })
                
                setTimeout(() => {
                    window.requestAnimationFrame(() => {
                        event.target.classList.remove('toClipboardDone')
                    })
                }, 500)
            })
        })
    })
})