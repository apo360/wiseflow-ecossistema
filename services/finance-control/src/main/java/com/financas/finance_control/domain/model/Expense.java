package com.financas.finance_control.domain.model;

import com.financas.finance_control.shared.enums.Frequency;
import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import java.math.BigDecimal;
import java.time.LocalDate;

@Entity
@Table(name = "expenses")
@Getter @Setter
public class Expense extends BaseEntity {

    @ManyToOne
    @JoinColumn(name = "category_id", nullable = false)
    private ExpenseCategory category;

    @Column(nullable = false)
    private String name;

    private String description;

    @Column(nullable = false)
    private BigDecimal amount;

    private BigDecimal minAmount;
    private BigDecimal maxAmount;

    @Enumerated(EnumType.STRING)
    private Frequency frequency;

    private LocalDate dueDate;

    private Boolean isFixed = true;
    private Boolean isActive = true;

    @Column(name = "user_id")
    private Long userId;
}
