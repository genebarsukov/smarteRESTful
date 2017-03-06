/**
 * Created by Gene on 1/1/2017.
 */
export class ProgressButton {
    text: string = '';
    start_text: string = '';
    working_text: string = '';
    success_text: string = 'Done';
    error_text: string = 'Error'
    timeout_text: string = 'Time Out';
    progress_text: string = '';
    progress_char: string = '.';
    work_progress: NodeJS.Timer;
    wait_progress: NodeJS.Timer;
    timeout_limit: number = 30000;
    timer: number = 0;
    start_delay: number = 10;
    waiting_number_interval = 200;

    status: string = 'free'; // free, waiting, working, off
    interrupt_waiting: boolean = true;
    interrupt_working: boolean = true;
    work_action: Function;
    finish_action: Function;


    constructor(start_text, working_text, start_delay, work_action, finish_action) {
        this.start_text = start_text;
        this.text = this.start_text;
        this.working_text = working_text;
        this.start_delay = start_delay;
        this.work_action = work_action;
        this.finish_action = finish_action;
    }

    /**
     * Invoked when the button is pressed.
     * The button may be in 4 states at this point: waiting, working, free, or off. All 3 are handled differently
     */
    press() {
        if (this.status == 'waiting') {
            if(this.interrupt_waiting) {
                this.interrupt('wait');
            }
        } else if(this.status == 'working') {
            if(this.interrupt_working) {
                this.interrupt('work');
            }
        } else if (this.status == 'free') {
            let self = this;
            if(this.start_delay > 100) {
                let wait_callback = function () {
                    clearInterval(self.wait_progress);
                    self.text = self.working_text;
                    self.work(self);
                };
                this.wait(wait_callback);
            } else if(this.start_delay > 0) {
                setTimeout(function(){
                    self.work(self);
                }, this.start_delay);
            } else {
                self.work(self);
            }
        } else {
            this.off();
        }
    }

    /**
     * Interruption by user
     * Invoked when the button is pressed while it is in waiting or working states
     */
    interrupt(interrupting) {
        this.text = this.start_text;
        this.progress_text = '';

        if (interrupting == 'wait') {
            if (this.wait_progress) {
                clearInterval(this.wait_progress);
                this.status = 'free';
            }
        } else if (interrupting == 'work') {
            if (this.work_progress) {
                this.finish('Interrupt');
            }
        }

    }

    /**
     * End the button progress after its work() has completed
     */
    endGracefully() {
        this.finish('Done');
    }

    /**
     * Invoked when the button is pressed and it is in a 'free' state
     * @param wait_callback: What is to be done after waiting. Currently work() is invoked
     */
    wait(wait_callback) {
        this.status = 'waiting';
        let counter = parseInt(this.start_delay / this.waiting_number_interval);
        this.text = counter.toString();
        let self  = this;
        self.wait_progress = setInterval(function() {
            counter --;
            if (counter <= 0) {
                clearInterval(self.wait_progress);
                wait_callback();
            } else {
                self.text = counter.toString();
            }
        }, self.waiting_number_interval);
    }

    /**
     * Invoked after wait() is finished or if the waiting period is specified as null and the button is in a free state
     * @param self: A reference to this ProgressButton object
     */
    work(self) {
        this.status = 'working';
        // Do work specified in the specified work_action function
        if (self.work_action) {
            self.work_action();
        }
        self.work_progress = setInterval(function () {

            if (self.progress_text == (self.progress_char + self.progress_char + self.progress_char + self.progress_char + self.progress_char)) {
                self.progress_text = '';
            } else {
                self.progress_text += self.progress_char;
            }
            self.timer += 500;
            if (self.timer >= self.timeout_limit) {
                clearInterval(self.work_progress);
                self.timer = 0;
                self.finish('Time Out')
            }
        }, 500);
    }

    /**
     * Invoked if the button is in the 'off' state - the button performs no real tasks in this case
     */
    off() {}

    /**
     * Invoked when the button finished the work() method. It resets the button status and displays a final message
     * on the button relating to how the task went
     * @param message
     */
    finish(message) {
        // prevent any button actions while finishing
        this.status = 'off';
        clearInterval(this.work_progress);
        this.progress_text = '';

        if (message != null) {
            this.text = message;
        } else {
            this.text = this.success_text;
        }
        if (this.finish_action) {
            this.finish_action();
        }
        let self = this;
        setTimeout(function() {
            self.text = self.start_text;
            self.status = 'free';
        }, 1500)

    }
}