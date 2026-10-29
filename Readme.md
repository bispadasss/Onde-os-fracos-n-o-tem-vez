# 🎮 Quiz Interativo e Sistema de Ranking

Projeto acadêmico desenvolvido para a disciplina de **Lógica de Programação**, com o objetivo de criar um quiz interativo de perguntas e respostas com pontuação acumulativa e sistema de classificação dos jogadores, inspirado no Kahoot.

---

## 📌 Sobre o Projeto

O sistema permitirá que os jogadores respondam a perguntas, acumulem pontos de acordo com seu desempenho e acompanhem sua posição no ranking geral.

A aplicação será desenvolvida utilizando **PHP** para a lógica do sistema e **HTML5** e **CSS3** para a construção da interface web.

O projeto também tem como finalidade aplicar conceitos fundamentais de programação, estruturas de dados, algoritmos de busca e ordenação e manipulação de arquivos.

---

## 🎯 Objetivos

- Desenvolver um jogo de perguntas e respostas interativo.
- Implementar um sistema de pontuação acumulativa.
- Criar um ranking organizado pela pontuação dos jogadores.
- Aplicar estruturas de dados estudadas em Lógica de Programação.
- Armazenar perguntas, respostas e resultados em arquivos.
- Desenvolver uma interface web simples e intuitiva.

---

## 🛠️ Tecnologias

- **PHP:** processamento das respostas, regras do quiz e cálculo da pontuação.
- **HTML5:** estrutura das páginas e formulários.
- **CSS3:** estilização e organização visual da interface.
- **Arquivos JSON, CSV ou TXT:** armazenamento de perguntas e histórico de pontuações, conforme a implementação escolhida.

---

## ⚙️ Funcionalidades Previstas

- Cadastro ou identificação dos jogadores por *nickname*.
- Exibição de perguntas e alternativas.
- Registro das respostas dos jogadores.
- Cálculo da pontuação acumulada.
- Revisão da última resposta selecionada.
- Exibição do ranking geral.
- Busca de jogadores no ranking.
- Salvamento e carregamento de dados por arquivos.

---

## 🧠 Estruturas de Dados e Algoritmos

| Estrutura ou Conceito | Aplicação no Projeto |
| :--- | :--- |
| **Fila (FIFO)** | Organização da sequência de perguntas ou da entrada dos jogadores. |
| **Pilha (LIFO)** | Histórico de respostas para permitir revisar ou desfazer a última alternativa selecionada antes do envio final. |
| **Matriz 2D** | Registro do desempenho dos jogadores por pergunta. |
| **Vetor 1D** | Armazenamento de alternativas ou pontuações acumuladas. |
| **Busca** | Localização de jogadores pelo *nickname* no ranking. |
| **Ordenação** | Classificação dos jogadores em ordem decrescente de pontuação. |
| **Persistência em arquivos** | Carregamento de perguntas e salvamento do histórico do ranking. |

---

## 📂 Estrutura de Arquivos

A organização abaixo é uma sugestão inicial e poderá ser adaptada conforme o desenvolvimento:

```text
quiz-ranking/
├── index.php
├── quiz.php
├── resultado.php
├── ranking.php
├── css/
│   └── style.css
├── dados/
│   ├── perguntas.json
│   └── ranking.json
└── README.md
```

---



## 📊 Sistema de Ranking

O ranking deverá organizar os jogadores pela pontuação total, da maior para a menor.

O projeto poderá utilizar algoritmos como **Bubble Sort**, **Selection Sort** ou **QuickSort** para a ordenação, além de busca linear ou binária para localizar jogadores, conforme os requisitos da atividade.

---

## 📚 Contexto Acadêmico

- **Disciplina:** Lógica de Programação
- **Projeto:** Proposta 5 — Quiz Interativo e Sistema de Ranking
- **Interface escolhida:** Aplicação web simplificada
- **Linguagem principal:** PHP
- **Tecnologias de interface:** HTML5 e CSS3

---

## 👨‍💻 Autores

- Bruno Batista
- Guilherme Moreira
- Lucas Bispo

---

> *Este README descreve os objetivos e a estrutura planejada do projeto. As funcionalidades e os arquivos deverão ser atualizados conforme forem implementados e testados.*
