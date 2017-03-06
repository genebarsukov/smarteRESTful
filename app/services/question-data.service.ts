import 'rxjs/add/operator/map';
import {Injectable} from '@angular/core';
import {Http, Headers, RequestOptions, URLSearchParams} from '@angular/http';
import {Subject} from 'rxjs/Subject';
import {ApiResponse} from "../models/api-response";
import {QuestionComponent} from "../components/question/question.component";
import {Distractor} from "../models/distractor";
import {Question} from "../models/Question";

@Injectable()
export class QuestionDataService {
    api_url: string = 'http://codewrencher.com/smarterestful/questions/';

    private question_deleted_subscribers = new Subject<number>();
    question_deleted$ = this.question_deleted_subscribers.asObservable();
    /**
     * Injecting the Http service into our data service
     */
    constructor(private http: Http) {}

    /**
     * GET
     * Gets a batch of news sources on initial page load
     * @param $pattern: Optional search pattern to search for questions
     * @returns {Question[]}
     */
    getQuestions(param_string: string) {
        let url = this.api_url;
        // getting with a search string will filter the results using that string
        if (param_string) {
            url += param_string;
        }
        let questions: any = this.http.get(url)
            .map(response => <ApiResponse>response.json());

        return questions;
    }

    /**
     * POST
     * Updates a question
     * @param question: QuestionComponent
     * @returns {any}
     */
    updateQuestion(question: Question) {
        let headers = new Headers({ 'Content-Type': 'application/x-www-form-urlencoded' });
        let options = new RequestOptions({headers: headers});

        let body = new URLSearchParams();;
        body.set('question', JSON.stringify(question));

        let url = this.api_url;
        // posting with an id will updade. Posing without will create a new Question and return its id
        if (question.question_id) {
            url += question.question_id.toString();
        }
        let updated_question = this.http.post(url, body, options)
            .map(response => <ApiResponse>response.json());
        
        return updated_question;
    }

    /**
     * DELETE
     * Deletes a question
     * @param question: QuestionComponent
     * @returns {any}
     */
    deleteQuestion(question: Question) {
        let headers = new Headers({ 'Content-Type': 'application/x-www-form-urlencoded' });
        let options = new RequestOptions({headers: headers});

        let deleted_question = this.http.delete(this.api_url + question.question_id.toString(), options)
            .map(response => <ApiResponse>response.json());

        return deleted_question;
    }

    /**
     * Notify any subscribers that a certain question was deleted
     * @param question_id: The id of the Question object
     */
    notifyOfQuestionDeleted(question_id: number) {
        this.question_deleted_subscribers.next(question_id);
    }

}