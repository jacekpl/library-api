<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260701213110 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create the book_event table (borrow/return history).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE book_event (id UUID NOT NULL, type VARCHAR(255) NOT NULL, card_number VARCHAR(6) NOT NULL, occurred_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, book_id UUID NOT NULL, PRIMARY KEY (id))');
        $this->addSql('CREATE INDEX IDX_356C1FDE16A2B381 ON book_event (book_id)');
        $this->addSql('ALTER TABLE book_event ADD CONSTRAINT FK_356C1FDE16A2B381 FOREIGN KEY (book_id) REFERENCES book (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE book_event DROP CONSTRAINT FK_356C1FDE16A2B381');
        $this->addSql('DROP TABLE book_event');
    }
}
