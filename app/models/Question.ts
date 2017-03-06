import {Distractor} from "./distractor";
/**
 * Created by Gene on 3/5/2017.
 */
export class Question {
    question_id: number;
    question: string;
    answer: string;
    distractors: Distractor[];
}