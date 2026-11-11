import './lazy.css'
import {shared} from './shared.js'

export function lazy() {
  console.log('lazy', shared())
}
