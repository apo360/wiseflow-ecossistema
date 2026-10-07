package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import java.math.BigDecimal;
import com.financas.finance_control.shared.enums.DistributionModel;

@Entity
@Table(name = "financial_distributions")
@Getter @Setter
public class FinancialDistribution extends BaseEntity {

    private BigDecimal savingsPercentage;
    private BigDecimal expensesPercentage;
    private BigDecimal investmentPercentage;

    @Enumerated(EnumType.STRING)
    private DistributionModel distributionModel;

    private Boolean isActive = true;
}
