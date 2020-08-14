window.addEventListener('load', event => {
    let multi = document.querySelectorAll("*[data-multi='1']")
    let changeFunction = event => {
        if(event.target.value === '') {
            if (event.target.nextElementSibling !== null && event.target.nextElementSibling.name === event.target.name) {
                event.target.parentNode.removeChild(event.target)
            } 
        } else {
            if (event.target.nextElementSibling !== null && event.target.nextElementSibling.name === event.target.name) { return }
            let newElement = event.target.cloneNode(true)
            newElement.value = ''
            newElement.addEventListener('change', changeFunction)
            event.target.parentNode.insertBefore(newElement, event.target.nextElementSibling)
        }
    }
    multi.forEach (element => {
        element.addEventListener('change', changeFunction)
    })
})