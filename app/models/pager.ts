/**
 * Created by Gene on 3/5/2017.
 */
export class Pager {
    page: number = 1;
    page_size: number = 100;
    total_records: number;
    max_pages: number;
    order: string = 'asc';
    order_column: string = 'question';
    pattern: string = '';
}