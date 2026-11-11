import './main.css'
import {shared} from './shared.js'
import logoUrl from './logo.svg'

document.querySelector('#app').innerHTML = `<h1>main ${shared()}</h1><img src="${logoUrl}" alt="">`

document.querySelector('#lazy')?.addEventListener('click', async () => {
  const {lazy} = await import('./lazy.js')
  lazy()
})
