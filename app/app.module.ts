import {NgModule}      from '@angular/core';
import {BrowserModule} from '@angular/platform-browser';
import {FormsModule} from '@angular/forms';
import {HttpModule} from '@angular/http';
import {QuestionDataService} from './services/question-data.service';
import {AppComponent}  from './components/app/app.component';
import {QuestionListComponent} from './components/question-list/question-list.component';
import {QuestionComponent} from './components/question/question.component';


@NgModule({
  imports: [ BrowserModule, FormsModule, HttpModule ],
  declarations: [
    AppComponent,
    QuestionListComponent,
    QuestionComponent
    ],
  bootstrap: [ AppComponent ],
  providers: [ QuestionDataService ]
})

export class AppModule {}

