import { Component } from '@angular/core';
import {QuestionDataService} from '../../services/question-data.service';


@Component({
  selector: 'app',
  templateUrl: 'app/components/app/app.component.html',
  styleUrls: ['app/components/app/app.component.css']
})
export class AppComponent {
  name = 'SmarteRESTful';

  /**
   * Injecting our story data service into this component
   */
  constructor(private question_data_service: QuestionDataService) {

  }

  /**
   * Called when the component is destroyed. Used to dispose of data subscriptions to avoid memoty leaks
   */
  ngOnDestroy() {
  }

}
  