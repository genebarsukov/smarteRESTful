/**
 * Created by Gene on 1/16/2017.
 */



class Loader {
   /**
    * Constructor: Displays a selection of loading phrases while Angular 2 is loading
    */
   constructor() {
        var loading_phrases = [
           'Loading the madness...',
           'To load or not to load...',
           'Loading...'
        ];
        var phrase_index = parseInt(Math.random() * loading_phrases.length);
        var phrase = loading_phrases[phrase_index];
        var loading_text = document.getElementById('app-loading-text');

        loading_text.textContent = phrase;
    }
}
/**
 * Perform action on window load
 */
window.onload = function() {
    let loader = new Loader();
}
