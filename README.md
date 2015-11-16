# moode-recommendation-service
Sistema de Recomendação de Conteúdos para Alunos com baixo rendimento no Moodle

Composto por 3 módulos:
- Módulo Professor - é responsável pela classificação de documentos a serem recomendados aos alunos
- Módulo Aluno - é responsável pelo acesso de conteúdos recomendados
- Módulo Web Service - é responsável pela análise e recomendação de conteúdo para alunos atráves de email e notificação no App Mobile

Como funciona?

O Serviço analisa as notas obtidas pelos alunos em seus respectivos cursos e, caso o aluno tenha nota inferior a 7 (ou 70%) será realizada uma recomendação de conteúdos da Web baseado no assunto da avaliação. Esse conteúdo serão enviados via email.
