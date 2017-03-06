import {QuestionComponent} from "../components/question/question.component";

export class ApiResponse {
    action: string;
    status: string;
    question_count: number;
    question_id: number;
    total_records: number;
    questions: QuestionComponent[];
}