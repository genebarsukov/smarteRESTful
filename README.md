#smarteRESTful API and web app

This is a simple web application

It uses a PHP back-end API which you can test here:
http://codewrencher.com/smarterestful/

It uses an Angular 2 front end which you can test here:
http://codewrencher.com/modules/smarterestful/

It uses MySQL as the database.

There is a python script that loads the data initially into the database in /data_loader

##Structure
###API Typical usages:
http://codewrencher.com/smarterestful/  Brings up all the questions from the first page, 100 by default
http://codewrencher.com/smarterestful/{id}  Returns the question with the specified id
http://codewrencher.com/smarterestful/?pattern=find_me  Returns the search results for all question fields containing 'pattern'
Paging and sorting is also done ny passing the current paging and sorting params

###Back End:
* All of the back-end code is in /app/api
* smarterestful.php is the main back end endpoint through which everything goes through.
* Requests are routed with an .htaccess file which actually lives outside the project:
* RewriteRule ^smarterestful(.*) /modules/smarterestful/app/api/smarterestful.php [L]
* RequestHandler parses and decides what to do with all the requests

###Front End:
* All of the front-end code lives in /app
* Angular 2 written in TypeScript
* The main functional components are in /app/components
* The component that handles most of the logic for the page is in /app/components/question-list/qurstion-list.components.ts

###A note on performance:
There is an easy way to reduce the Angular 2 loading time significantly by pre-compling all the TypeScript into its javascript intermediaries and to minify everything and put it in a nice package using rollup

This would probably reduce the loading times of this web app by at least 50%-75%, but it requires a compuling script to be run every time changes are made.

Since this is still a developmnet app, I decided to leave it in its current sprawling state.

The favicon comes from another Angular 2 app I recently built. It is also available on this github
