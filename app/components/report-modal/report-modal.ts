class ReportModalController {
  readonly Auth: Auth;
  readonly API: API;
  readonly user: Crucio.User;
  readonly $uibModal: angular.ui.bootstrap.IModalService;
  questionId: number;
  question: any;
  resolve: any;
  close: any;
  message: string;

  constructor(API: API, Auth: Auth, $uibModal: angular.ui.bootstrap.IModalService) {
    this.Auth = Auth;
    this.API = API;
    this.$uibModal = $uibModal;

    this.user = Auth.getUser();
  }

  $onInit() {
    this.questionId = this.resolve.questionId;
    this.question = this.resolve.question;
  }

  reportQuestion(): void {
    const validation = this.message;

    if (validation) {
      const data: Crucio.MailQuestion = {
        text: this.message,
        name: this.user.username,
        email: this.user.email,
        question_id: this.questionId,
        author: this.question.username,
        question: this.question.question,
        exam_id: this.question.exam_id,
        subject: this.question.subject,
        date: this.question.date,
        author_email: this.question.email,
        mail_subject: 'Allgemein',
      };
      this.API.post('contact/send-mail-question', data).then(() => {
        this.close({$value: 'reported'});
      });
    }
  }
}

angular.module('crucioApp').component('reportModalComponent', {
  templateUrl: 'app/components/report-modal/report-modal.html',
  controller: ReportModalController,
  bindings: {
    resolve: '<',
    close: '&',
    dismiss: '&'
  }
});