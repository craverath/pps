# API de Pagamentos Simplificada

API RESTful desenvolvida com Laravel 10 e PHP 8.1 para uma plataforma de pagamentos simplificada que permite depósitos e transferências de dinheiro entre usuários.

## Requisitos

- PHP 8.1 ou superior
- Composer
- MySQL 5.7 ou superior
- Laravel 10

## Instalação

1. Clone o repositório:
```bash
git clone https://github.com/seu-usuario/api-pagamentos.git
cd api-pagamentos
```

2. Instale as dependências:
```bash
composer install
```

3. Copie o arquivo de ambiente:
```bash
cp .env.example .env
```

4. Configure o banco de dados no arquivo `.env`:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=nome_do_banco
DB_USERNAME=seu_usuario
DB_PASSWORD=sua_senha
```

5. Gere a chave da aplicação:
```bash
php artisan key:generate
```

6. Execute as migrações:
```bash
php artisan migrate
```

7. Inicie o servidor de desenvolvimento:
```bash
php artisan serve
```

## Endpoints

### Usuários

#### Criar usuário
```
POST /api/users
```

**Payload:**
```json
{
    "nome_completo": "João Silva",
    "cpf_cnpj": "12345678900",
    "email": "joao@email.com",
    "password": "senha123",
    "tipo_usuario": "comum"
}
```

**Resposta de sucesso (201):**
```json
{
    "id": 1,
    "nome_completo": "João Silva",
    "cpf_cnpj": "12345678900",
    "email": "joao@email.com",
    "tipo_usuario": "comum",
    "saldo_inicial": 0.00,
    "created_at": "2024-04-05T12:00:00.000000Z"
}
```

### Transferências

#### Realizar transferência
```
POST /api/transfer
```

**Payload:**
```json
{
    "value": 100.00,
    "payer": 1,
    "payee": 2
}
```

**Resposta de sucesso (200):**
```json
{
    "message": "Transferência realizada com sucesso",
    "transaction_id": 1
}
```

## Regras de Negócio

### Usuários
- Existem dois tipos de usuários: Comum e Lojista
- Usuários comuns podem enviar e receber dinheiro
- Lojistas podem apenas receber dinheiro
- Cada usuário possui uma carteira com saldo

### Transferências
- Apenas usuários do tipo Comum podem enviar dinheiro
- Lojistas não podem enviar dinheiro
- O pagador deve ter saldo suficiente
- Antes de completar a transferência, o sistema consulta um serviço externo de autorização
- A operação é atômica e transacional (realiza rollback em caso de falha)
- Após a transferência, o sistema envia uma notificação ao recebedor

## Testes

Para executar os testes:

```bash
php artisan test
```

## Documentação da API

A documentação completa da API está disponível em:
- [Postman Collection](docs/postman-collection.json)
- [Swagger/OpenAPI](docs/swagger.yaml)

## Contribuindo

1. Faça um fork do projeto
2. Crie uma branch para sua feature (`git checkout -b feature/AmazingFeature`)
3. Commit suas mudanças (`git commit -m 'Add some AmazingFeature'`)
4. Push para a branch (`git push origin feature/AmazingFeature`)
5. Abra um Pull Request

## Licença

Este projeto está licenciado sob a licença MIT - veja o arquivo [LICENSE](LICENSE) para detalhes.
