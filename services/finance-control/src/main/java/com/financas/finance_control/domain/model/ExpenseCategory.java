package com.financas.finance_control.domain.model;

import com.financas.finance_control.shared.enums.ExpenseType;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Entity
@Table(name = "expense_categories")
@Getter @Setter
public class ExpenseCategory extends BaseEntity {

    @Column(nullable = false)
    private String name;

    private String description;

    @Enumerated(EnumType.STRING)
    private ExpenseType type;

    private Integer priority;
}
