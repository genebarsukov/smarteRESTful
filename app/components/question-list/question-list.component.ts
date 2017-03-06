import {Component, OnDestroy} from '@angular/core';
import {QuestionDataService} from '../../services/question-data.service';
import {QuestionComponent} from "../question/question.component";
import {PagerComponent} from "../pager/pager.component"
import {Subscription} from 'rxjs/Subscription';
import {Question} from "../../models/Question";
import {Pager} from "../../models/pager";
import {ApiResponse} from "../../models/api-response";

@Component({
    selector: 'question-list',
    templateUrl: 'app/components/question-list/question-list.component.html',
    styleUrls: ['app/components/question-list/question-list.component.css'],
})

export class QuestionListComponent implements OnDestroy {
    data_subscription: Subscription;
    question_deleted_subscription: Subscription;
    questions: QuestionComponent[] = [];
    search_text_hint: string = "Search any field pertaining to questions";
    search_pattern: string = '';
    pager: Pager = new Pager();
    

    /**
     * Injecting our question data service into this component
     */
    constructor(private question_data_service: QuestionDataService) {
        this.listenForQuestionDeletions();
        this.getQuestions('');
    }

    /**
     * Subscribe to get notified when a child question is deleted
     */
    listenForQuestionDeletions() {
        this.question_deleted_subscription = this.question_data_service.question_deleted$.subscribe(
            question_id => this.removeQuestionFromList(question_id)
        );
    }
    /**
     * Get a batch of questions on initial load
     */
    getQuestions($param_string) {
        this.data_subscription = this.question_data_service.getQuestions($param_string).subscribe(
            questions => this.finishGetQuestions(questions)
        );
    }
    /**
     * Callback: Called when getQuestions() response is received from the server
     * @param questions
     */
    finishGetQuestions(api_response: ApiResponse) {
        this.questions = null;
        this.questions = api_response.questions;
        this.pager.total_records = api_response.total_records;
        this.pager.max_pages = Math.ceil(this.pager.total_records / this.pager.page_size);
    }

    /**
     * Remove a question from the main list after it has been deleted by the child component
     */
    removeQuestionFromList(question_id: number) {
        this.questions = this.questions.filter(function(question) {
            return question.question_id !== question_id;
        });
    }

    /**
     * Add a brand new question for you to fill out
     * First go to the back end, get the new id, then display the new question on the UI
     */
    addNewQuestion() {
        this.data_subscription = this.question_data_service.updateQuestion(new Question()).subscribe(
            question => this.finishAddNewQuestion(question)
        );
    }

    /**
     * Callback: Called when getQuestions() response is received from the server
     * @param questions
     */
    finishAddNewQuestion(api_response: ApiResponse) {
        this.questions.splice(0, 0, api_response.questions[0]);
    }

    /**
     * Initiate search when hitting Enter inside the search box
     * @param event: Triggering even
     */
    searchQuestions(event) {
        // reset the paging to 1 when searching
        this.pager.page = 1;

        let param_string = this.buildParamStringFromPager();
        //let param_string = '?pattern=' + encodeURIComponent(this.search_pattern.toString());
        this.getQuestions(param_string);

        event.stopPropagation();
    }

    /**
     * Initiate search when hitting Enter inside the search box
     * @param event:: Key event
     */
    onInputKeyPress(event: any) {
        if (event.key == "Enter") {
            this.searchQuestions(event);
        }
    }

    /**
     * Sort the curect question on the backend and reload the results
     * @param order_column: The column to sort on
     */
    sortQuestions(order_column: string) {
        this.updatePagingParams(order_column);
        let param_string = this.buildParamStringFromPager();

        this.getQuestions(param_string);
    }

    /**
     * Transform all pager properties into a param sting that can be used in a url
     * @returns {string}
     */
    buildParamStringFromPager() {
        // update the pager search string
        this.pager.pattern = encodeURIComponent(this.search_pattern.toString())

        let param_string = '?'
        let params = [];
        for (let prop in this.pager) {
            params.push(prop + '=' + this.pager[prop]);
        }
        param_string += params.join('&');

        return param_string;
    }

    /**
     * Get all Questions on the next page
     */
    pageUp() {
        this.pager.max_pages = Math.ceil(this.pager.total_records / this.pager.page_size);
        // if we are on the last page
        if (this.pager.page >= this.pager.max_pages) {
            return;
        }
        // otherwise increment our page number and keep going
        this.pager.page += 1;
        let param_string = this.buildParamStringFromPager();

        this.getQuestions(param_string);
    }

    /**
     * Get all Questions on the previous page
     */
    pageDown() {
        // if we are on the first page
        if (this.pager.page <= 1) {
            return;
        }
        // otherwise decrement our page number and keep going
        this.pager.page -= 1;
        let param_string = this.buildParamStringFromPager();

        this.getQuestions(param_string);
    }

    /**
     * Calculate new paging params to send to the back end based on previous data
     * @param sort_column: Column to sort on
     */
    updatePagingParams(order_column: string) {
        // flip the paging order
        if (order_column == this.pager.order_column) {
            if(this.pager.order == 'asc') {
                this.pager.order = 'desc';
            } else {
                this.pager.order = 'asc';
            }
        }
        // when sorting a new column, set ascending as the default sort order
        else {
            this.pager.order_column = order_column;
            this.pager.order = 'asc';
        }
        // add a search string if present so we can sort just the search dataset
        if (this.search_pattern) {
            this.pager.pattern = this.search_pattern;
        }
    }

    /**
     * Called when the component is destroyed
     */
    ngOnDestroy() {
        // Unsubscribe from the data service when this module is destroyed
        if (this.data_subscription != null) {
            this.data_subscription.unsubscribe();
        }
        if (this.question_deleted_subscription != null) {
            this.question_deleted_subscription.unsubscribe();
        }
    }
    
}