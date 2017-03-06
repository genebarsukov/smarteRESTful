import {Distractor} from "../../models/distractor";
import {Component, OnDestroy, Input} from '@angular/core';
import {QuestionDataService} from 'app/services/question-data.service';
import {Subscription} from 'rxjs/Subscription';
import {Question} from "../../models/Question";
import {ApiResponse} from "../../models/api-response";

@Component({
    selector: 'question',
    templateUrl: 'app/components/question/question.component.html',
    styleUrls: ['app/components/question/question.component.css']

})

export class QuestionComponent implements OnDestroy {
    @Input() question_id: number;
    @Input() question: string;
    @Input() answer: string;
    @Input() distractors: Distractor[];
    data_subscription: Subscription;
    editing;

    /**
     * Injecting our story data service into this component
     */
    constructor(private question_data_service: QuestionDataService) {}



    /**
     * Set editing status when editing a question
     * This will change how the question looks in the UI
     */
    editQuestion() {
        this.editing = true;
    }

    /** EDITING and UPDATING questions */

    /**
     * Goes to the back end and saves the current data
     */
    saveQuestion() {
        this.data_subscription = this.question_data_service.updateQuestion(this.convertSelfToObject()).subscribe(
            updated_question => this.finishSaveQuestion(updated_question)
        );
    }

    /**
     * Callback: Called when updateQuestion() response is received from the server
     * @param api_response: Returned date for the updated question
     */
    finishSaveQuestion(api_response: ApiResponse) {
        let updated_question = api_response.questions[0];
        this.editing = false;

        if (updated_question != null) {
            console.log('updated successfully');
        }
        else {
            console.log('failure');
        }
    }

    /**
     * Goes to the back end and deletes the question
     * If successful, the question is then removed from the UI
     */
    deleteQuestion() {
        this.data_subscription = this.question_data_service.deleteQuestion(this.convertSelfToObject()).subscribe(
            deleted_question => this.finishDeleteQuestion(deleted_question)
        );
    }

    /**
     * Callback: Called when deleteQuestion() response is received from the server
     * @param api_response: Returned data for the deleted question
     */
    finishDeleteQuestion(api_response: ApiResponse) {
        let deleted_questions = api_response.questions;
        this.editing = false;

        if (deleted_questions.length) {
            console.log('deleted successfully');

            let question_id = deleted_questions[0].question_id;
            this.question_data_service.notifyOfQuestionDeleted(question_id);
        }
        else {
            console.log('failed to delete');
        }

    }
    /**
     * Add a new Distractor box
     * This guy does not immediately go to the back end
     */
    addDistractor() {
        let distractor = new Distractor();
        distractor.question_id = this.question_id;

        this.distractors.push(distractor)
    }

    /**
     * Close the opened question without saving
     */
    collapseQuestion() {
        this.editing = false;
    }

    /**
     * Save when pressing enter in an input box
     * @param event
     */
    onInputKeyPress(event: any) {
        if (event.key == "Enter") {
            this.saveQuestion();
        }
    }

    /**
     * Convert self to a Question object before sending that data to the back end.
     * This will prevent things like circularly stringifying injectable dependencies when converting to json.
     */
    convertSelfToObject() {
        let question = new Question();
        question.question_id = this.question_id;
        question.question = this.question;
        question.answer = this.answer;
        question.distractors = this.distractors;
        
        return question;
    }
    /**
     * Called when the component is destroyed
     */
    ngOnDestroy() {
        // Unsubscribe from the data service when this module is destroyed
        if (this.data_subscription != null) {
            this.data_subscription.unsubscribe();
        }
    }
}