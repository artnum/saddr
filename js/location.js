/* eslint-env browser */
(function () {
  let formatDate = function (date) {
    return `${date.toLocaleString('default', {weekday: 'short'})} ${date.getDate()}.${date.getMonth() + 1}.${date.getFullYear()}`
  }

  let process = function (json) {
    if (!json.success || json.length <= 0) { return }
    let parent = document.createElement('SECTION')
    parent.innerHTML = '<h2>Réservations</h2><div></div>'
    parent.classList.add('fullWidth')
    window.requestAnimationFrame(() => document.getElementById('saddr_content').appendChild(parent))

    let requests = []
    for (let i = 0; i < json.length; i++) {
      let url = new URL(window.Conf.location.server ? `${window.Conf.location.server}/${window.Conf.location.path}/DeepReservation/${json.data[i].reservation}` : `${window.location.origin}/${window.Conf.location.path}/DeepReservation/${json.data[i].reservation}`)
      requests.push(fetch(url).then((response) => {
        if (response.ok) {
          return response.json()
        }
        return null
      }))
    }
    let today = new Date()
    let timeline = {
      past: document.createElement('DIV'),
      futur: document.createElement('DIV'),
      current: document.createElement('DIV')
    }
    let tableHeader = '<tr><th>Numéro</th><th>Comme</th><th>Début</th><th>Fin</th><th>Référence</th><th>Localité</th></tr>'
    timeline.past.innerHTML = `<h3>Passée</h3><table>${tableHeader}</table>`
    timeline.futur.innerHTML = `<h3>Future</h3><table>${tableHeader}</table>`
    timeline.current.innerHTML = `<h3>En cours</h3><table>${tableHeader}</table>`
    window.requestAnimationFrame(() => {
      ['current', 'futur', 'past'].forEach((x) => {
        timeline[x].classList.add('third')
        parent.lastElementChild.appendChild(timeline[x])
      })
    })
    Promise.all(requests).then((results) => {
      for (let i = 0; i < results.length; i++) {
        if (!results[i] || !results[i].success || results[i].length <= 0) { continue }
        let reservation = results[i].data
        if (reservation.deleted !== null) { continue }
        for (let k in reservation) {
          if (reservation[k] === null) { reservation[k] = '' }
        }
        let comment = json.data[i].comment
        switch (comment) {
          case '_place': comment = 'Contact sur place'; break
          case '_client': comment = 'Client'; break
          case '_responsable': comment = 'Responsable'; break
          case '_facturation': comment = 'Facturation'; break
          case '_retour': comment = 'Retour'; break
        }
        let begin = reservation.deliveryBegin ? new Date(reservation.deliveryBegin) : new Date(reservation.begin)
        let end = reservation.deliveryEnd ? new Date(reservation.deliveryEnd) : new Date(reservation.end)
        let when = 'past'
        if (begin.getTime() <= today.getTime()) {
          if (end.getTime() >= today.getTime()) {
            when = 'current'
          }
        } else {
          when = 'futur'
        }
        let html = document.createElement('TR')
        html.innerHTML = `<td class="value"><a href="${window.Conf.location.url.replace('%', reservation.id)}">${reservation.id}</a></td>
                          <td class="value">${comment}</td>
                          <td class="value">${formatDate(begin)}</td>
                          <td class="value">${formatDate(end)}</td>
                          <td class="value">${reservation.reference}</td>
                          <td class="value">${reservation.locality}</td>
                         `
        window.requestAnimationFrame(() => timeline[when].lastElementChild.appendChild(html))
      }
    })
  }

  if (window.Conf && window.Conf.location) {
    window.addEventListener('load', function (event) {
      let inputs = document.body.getElementsByTagName('INPUT')
      let dn = null
      for (let i = 0; i < inputs.length; i++) {
        if (inputs[i].getAttribute('name') === 'dn') {
          dn = inputs[i].value
          break
        }
      }
      if (dn !== null) {
        let ident = encodeURIComponent(dn.split(',')[0])
        if (window.Conf.location.path) {
          let url = new URL(window.Conf.location.server ? `${window.Conf.location.server}/${window.Conf.location.path}/ReservationContact` : `${window.location.origin}/${window.Conf.location.path}/ReservationContact`)
          url.searchParams.append('search.target', `/Contacts/${ident}`)
          fetch(url).then((response) => {
            if (response.ok) {
              response.json().then((json) => {
                process(json)
              })
            }
          })
        }
      }
    })
  }
}())
